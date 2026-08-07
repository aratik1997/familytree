<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    public function test_only_a_super_admin_can_reach_it(): void
    {
        $this->get('/admin/mail-check')->assertRedirect('/login');

        $this->actingAs(User::factory()->create(['is_super_admin' => false]))
            ->get('/admin/mail-check')
            ->assertForbidden();

        $this->actingAs($this->superAdmin())
            ->get('/admin/mail-check')
            ->assertOk();
    }

    public function test_it_reports_the_mailer_actually_in_use(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.example.test']);

        $this->actingAs($this->superAdmin())
            ->get('/admin/mail-check')
            ->assertSee('smtp.example.test');
    }

    public function test_it_warns_when_email_is_switched_off(): void
    {
        config(['mail.default' => 'log']);

        $this->actingAs($this->superAdmin())
            ->get('/admin/mail-check')
            ->assertSee('Email is switched off.');
    }

    public function test_the_password_is_never_shown(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.password' => 'super-secret-value',
        ]);

        $this->actingAs($this->superAdmin())
            ->get('/admin/mail-check')
            ->assertDontSee('super-secret-value');
    }

    /**
     * Mail::fake() cannot assert on raw messages, so these capture the real
     * thing through the in-memory transport instead.
     */
    private function captured(): array
    {
        // ArrayTransport hands back a Collection of SentMessage.
        return app('mailer')->getSymfonyTransport()->messages()
            ->map(fn ($sent) => $sent->getOriginalMessage())
            ->all();
    }

    public function test_the_test_message_can_only_go_to_the_admins_own_address(): void
    {
        config(['mail.default' => 'array']);
        app('mailer')->getSymfonyTransport()->flush();

        $admin = $this->superAdmin();

        // Even when asked to send elsewhere, it uses the signed-in address.
        $this->actingAs($admin)
            ->post('/admin/mail-check', ['to' => 'stranger@example.test'])
            ->assertRedirect()
            ->assertSessionHas('mail_sent', $admin->email);

        $recipients = [];
        foreach ($this->captured() as $message) {
            foreach ($message->getTo() as $address) {
                $recipients[] = $address->getAddress();
            }
        }

        $this->assertSame([$admin->email], $recipients);
        $this->assertNotContains('stranger@example.test', $recipients);
    }

    public function test_a_non_admin_cannot_send(): void
    {
        config(['mail.default' => 'array']);
        app('mailer')->getSymfonyTransport()->flush();

        $this->actingAs(User::factory()->create(['is_super_admin' => false]))
            ->post('/admin/mail-check')
            ->assertForbidden();

        $this->assertCount(0, $this->captured());
    }
}
