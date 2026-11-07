<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteWebParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
        config(['lens.coming_soon' => false]);
    }

    public function test_auth_pages_and_account_shell_are_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Welcome back');
        $this->get('/join')->assertOk()->assertSee('How will you use Lens');
        $this->get('/register')->assertOk()->assertSee('Create your client account');
        $this->get('/vendors/join')
            ->assertOk()
            ->assertSee('Become a Lens vendor')
            ->assertSee('Previous projects')
            ->assertSee('Add previous project');

        $this->post('/login', [
            'email' => 'client@lens.app',
            'password' => 'password',
        ])->assertRedirect('/account');

        $this->get('/account')->assertOk()->assertSee('Edit profile');
        $this->get('/favorites')->assertOk();
        $this->get('/notifications')->assertOk();
        $this->get('/messages')->assertOk();
        $this->get('/account/wallet')->assertOk();
        $this->get('/ai')->assertOk()->assertSee('AI creative match');
    }

    public function test_web_login_uses_same_credentials_as_api_auth(): void
    {
        $api = $this->postJson('/api/app/auth/login', [
            'email' => 'client@lens.app',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('email', 'client@lens.app')
            ->json();

        $this->post('/login', [
            'email' => 'client@lens.app',
            'password' => 'password',
        ])->assertRedirect('/account');

        $this->assertEquals(
            (int) $api['id'],
            (int) session('lens_web_user_id'),
            'Web login session must match the API auth user id.',
        );

        $this->get('/account')
            ->assertOk()
            ->assertSee($api['name']);

        $this->post('/logout')->assertRedirect('/');

        $this->post('/login', [
            'email' => 'client@lens.app',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_marketplace_vendor_cards_expose_cover_and_profile_images(): void
    {
        $html = $this->get('/marketplace')
            ->assertOk()
            ->assertSee('Popular Photographer')
            ->assertSee('vendor-card', false)
            ->getContent();

        $this->assertGreaterThanOrEqual(10, substr_count($html, 'vendor-card'));
        $this->assertDoesNotMatchRegularExpression(
            "#src=\"(?:/storage/portfolios/|/storage/vendors/)[^\"]+\"#",
            $html,
            'Seeded vendor cards should not point at missing local portfolio/vendor files.',
        );
        $this->assertMatchesRegularExpression(
            '#class="cover"[\\s\\S]*?<img[^>]+src="https?://[^"]+"#',
            $html,
        );
    }

    public function test_vendor_profile_book_cta_points_to_web_booking(): void
    {
        $vendor = Vendor::query()->where('is_active', true)->where('verification_status', 'verified')->first()
            ?? Vendor::query()->where('is_active', true)->firstOrFail();

        $this->get('/creatives/'.$vendor->id)
            ->assertOk()
            ->assertSee('Book Now')
            ->assertDontSee('/admin/login', false);

        $this->actingAsSiteUser('client@lens.app');

        $this->get('/book/'.$vendor->id)
            ->assertOk()
            ->assertSee('Choose a package');
    }

    public function test_newsletter_persists_subscribers(): void
    {
        $this->post('/newsletter', ['email' => 'fan@lens.app'])
            ->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'fan@lens.app',
        ]);
    }

    public function test_web_chat_sends_message_and_creates_notification(): void
    {
        $vendor = Vendor::query()->where('display_name', 'Fahad Studio Light')->firstOrFail();
        $vendorUser = User::query()->where('email', 'vendor@lens.app')->firstOrFail();
        $before = $vendorUser->notifications()
            ->get()
            ->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message')
            ->count();

        $this->actingAsSiteUser('client@lens.app');

        $open = $this->get('/messages/vendor/'.$vendor->id);
        $open->assertRedirect();
        preg_match('#/messages/(\d+)#', (string) $open->headers->get('Location'), $matches);
        $conversationId = (int) ($matches[1] ?? 0);
        $this->assertGreaterThan(0, $conversationId);

        $this->get('/messages/'.$conversationId)
            ->assertOk()
            ->assertSee('Write a message', false);

        $body = 'Web chat ping '.uniqid();
        $this->postJson('/messages/'.$conversationId, ['body' => $body])
            ->assertCreated()
            ->assertJsonPath('message.body', $body);

        $this->get('/messages/'.$conversationId.'/poll')
            ->assertOk()
            ->assertJsonFragment(['body' => $body]);

        $vendorUser->refresh();
        $after = $vendorUser->notifications()
            ->get()
            ->filter(fn ($n) => ($n->data['title'] ?? null) === 'New message')
            ->count();
        $this->assertGreaterThan($before, $after);

        $this->actingAsSiteUser('vendor@lens.app');
        $this->get('/notifications')
            ->assertOk()
            ->assertSee('New message')
            ->assertSee($body, false);
    }

    protected function actingAsSiteUser(string $email): User
    {
        $user = User::query()->where('email', $email)->firstOrFail();
        $this->withSession(['lens_web_user_id' => $user->id]);

        return $user;
    }
}
