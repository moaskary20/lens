<?php

namespace Tests\Feature;

use App\Filament\Resources\IssueReportResource\Pages\EditIssueReport;
use App\Models\IssueReport;
use App\Models\User;
use App\Support\LensNotifier;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IssueReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_client_can_submit_an_issue_and_admin_sees_it(): void
    {
        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/issues', [
                'topic' => 'payment',
                'subject' => 'Escrow still pending',
                'body' => 'I paid last night and the booking is still waiting.',
                'booking_reference' => 'LN-1001',
                'app_version' => '1.0.0',
                'platform' => 'android',
            ])
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reference', 'ISS-0001');

        $issue = IssueReport::query()->firstOrFail();
        $this->assertSame('payment', $issue->topic);
        $this->assertSame('Sarah Bennett', $issue->name);
        $this->assertSame('client@lens.app', $issue->email);
        $this->assertNotNull($issue->user_id);

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->assertTrue(
            $admin->notifications()->get()->contains(fn ($note) => ($note->data['title'] ?? '') === 'New app issue'),
        );

        $this->actingAs($admin)
            ->get('/admin/issue-reports')
            ->assertOk()
            ->assertSee('ISS-0001')
            ->assertSee('Escrow still pending')
            ->assertSee('App issues');

        $this->actingAs($admin)
            ->get('/admin/issue-reports/'.$issue->id.'/edit')
            ->assertOk()
            ->assertSee('Escrow still pending')
            ->assertSee('Reply to reporter');
    }

    public function test_guest_report_is_not_attached_to_the_demo_client(): void
    {
        $this->withHeaders(['X-Lens-Client' => 'client@lens.app'])
            ->postJson('/api/app/issues', [
                'topic' => 'app',
                'subject' => 'Map is blank',
                'body' => 'The map page stays black after I open it.',
                'name' => 'Guest Client',
                'email' => 'guest.issue@lens.app',
                'guest' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('reference', 'ISS-0001');

        $issue = IssueReport::query()->firstOrFail();
        $this->assertNull($issue->user_id);
        $this->assertSame('Guest Client', $issue->name);
        $this->assertSame('guest.issue@lens.app', $issue->email);
    }

    public function test_admin_reply_notifies_the_reporter(): void
    {
        $client = User::query()->where('email', 'client@lens.app')->firstOrFail();
        $issue = IssueReport::query()->create([
            'user_id' => $client->id,
            'name' => $client->name,
            'email' => $client->email,
            'topic' => 'account',
            'subject' => 'Cannot edit profile',
            'body' => 'Save does nothing on the profile screen.',
            'status' => 'open',
        ]);

        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(EditIssueReport::class, ['record' => $issue->getKey()])
            ->fillForm([
                'status' => 'resolved',
                'staff_reply' => 'Please update the app and try again.',
                'admin_notes' => 'Reproduced on Android 14.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('resolved', $issue->fresh()->status);
        $this->assertNotNull($issue->fresh()->resolved_at);
        $this->assertTrue(
            $client->fresh()->notifications()->get()->contains(
                fn ($note) => ($note->data['event'] ?? '') === LensNotifier::ISSUE_UPDATED
                    && str_contains((string) ($note->data['body'] ?? ''), 'update the app'),
            ),
        );
    }
}
