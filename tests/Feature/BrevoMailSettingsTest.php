<?php

namespace Tests\Feature;

use App\Filament\Pages\PlatformSettings;
use App\Models\Setting;
use App\Models\User;
use App\Support\BrevoMail;
use Database\Seeders\LensSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrevoMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LensSeeder::class);
    }

    public function test_platform_settings_show_brevo_email_fields(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();

        $this->actingAs($admin)
            ->get('/admin/platform-settings')
            ->assertOk()
            ->assertSee('Brevo email', false)
            ->assertSee('Enable Brevo', false)
            ->assertSee('Send Brevo test email', false);
    }

    public function test_admin_can_save_brevo_settings_without_wiping_secrets(): void
    {
        $admin = User::query()->where('email', 'admin@lens.app')->firstOrFail();
        $this->actingAs($admin);

        Livewire::test(PlatformSettings::class)
            ->fillForm([
                'brevo_enabled' => true,
                'brevo_transport' => 'smtp',
                'brevo_api_key' => 'xkeysib-test-key',
                'brevo_smtp_password' => 'smtp-test-key',
                'brevo_from_email' => 'hello@lens.app',
                'brevo_from_name' => 'Lens',
                'brevo_smtp_host' => 'smtp-relay.brevo.com',
                'brevo_smtp_port' => 587,
                'brevo_template_welcome' => 12,
                'brevo_clients_list_id' => 8,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(BrevoMail::enabled());
        $this->assertSame('xkeysib-test-key', BrevoMail::settings()['api_key']);
        $this->assertSame('smtp-test-key', BrevoMail::settings()['smtp_password']);
        $this->assertSame('12', BrevoMail::settings()['template_welcome']);
        $this->assertSame('8', BrevoMail::settings()['clients_list_id']);
        $this->assertSame('Lens', Setting::getValue('platform.app_name', 'Lens'));

        Livewire::test(PlatformSettings::class)
            ->fillForm([
                'brevo_enabled' => true,
                'brevo_from_name' => 'Lens Bookings',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('xkeysib-test-key', BrevoMail::settings()['api_key']);
        $this->assertSame('smtp-test-key', BrevoMail::settings()['smtp_password']);
        $this->assertSame('Lens Bookings', BrevoMail::settings()['from_name']);
    }

    public function test_enabled_brevo_smtp_settings_are_applied_to_mail_config(): void
    {
        Setting::setGroupValues('mail', BrevoMail::sanitize([
            'enabled' => true,
            'transport' => 'smtp',
            'smtp_host' => 'smtp-relay.brevo.com',
            'smtp_port' => 587,
            'smtp_username' => 'ops@lens.app',
            'smtp_password' => 'smtp-key',
            'from_email' => 'hello@lens.app',
            'from_name' => 'Lens',
        ]));

        BrevoMail::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp-relay.brevo.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, (int) config('mail.mailers.smtp.port'));
        $this->assertSame('ops@lens.app', config('mail.mailers.smtp.username'));
        $this->assertSame('smtp-key', config('mail.mailers.smtp.password'));
        $this->assertSame('hello@lens.app', config('mail.from.address'));
        $this->assertSame('Lens', config('mail.from.name'));
    }

    public function test_test_email_is_blocked_until_brevo_is_enabled(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Turn on Brevo email');

        BrevoMail::sendTest('admin@lens.app');
    }
}
