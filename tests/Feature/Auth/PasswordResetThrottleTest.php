<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Laravel refuses a second reset link until the throttle window passes. These
 * cover the countdown the form shows for it — including that it is never
 * shown for an address with no account, which would confirm the account
 * exists to whoever typed it.
 */
class PasswordResetThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function requestLinkFor(string $email)
    {
        return $this->post('/forgot-password', ['email' => $email]);
    }

    public function test_a_countdown_is_offered_after_a_link_is_sent(): void
    {
        $user = User::factory()->create();

        $response = $this->requestLinkFor($user->email);

        $response->assertSessionHas('status');
        $response->assertSessionHas('retry_email', $user->email);

        $wait = session('retry_after');
        $this->assertGreaterThan(0, $wait, 'A wait should be reported after sending.');
        $this->assertLessThanOrEqual(
            config('auth.passwords.users.throttle'),
            $wait,
            'The wait cannot exceed the configured throttle.'
        );
    }

    public function test_a_second_request_is_refused_and_still_reports_the_wait(): void
    {
        $user = User::factory()->create();

        $this->requestLinkFor($user->email);
        $response = $this->requestLinkFor($user->email);

        $response->assertSessionHasErrors('email');
        $this->assertGreaterThan(0, session('retry_after'));
        $this->assertSame($user->email, session('retry_email'));
    }

    public function test_the_countdown_shrinks_as_the_window_passes(): void
    {
        $user = User::factory()->create();
        $this->requestLinkFor($user->email);
        $first = session('retry_after');

        $this->travel(20)->seconds();

        $this->requestLinkFor($user->email);
        $second = session('retry_after');

        $this->assertLessThan($first, $second, 'The remaining wait should fall over time.');
        $this->assertEqualsWithDelta($first - 20, $second, 1);
    }

    public function test_a_new_link_is_allowed_once_the_window_has_passed(): void
    {
        $user = User::factory()->create();
        $this->requestLinkFor($user->email);

        $this->travel(config('auth.passwords.users.throttle') + 1)->seconds();

        $response = $this->requestLinkFor($user->email);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
    }

    public function test_no_countdown_is_shown_for_an_address_with_no_account(): void
    {
        $response = $this->requestLinkFor('nobody@example.test');

        $response->assertSessionHasErrors('email');
        $this->assertSame(0, session('retry_after'));
        $this->assertNull(session('retry_email'));
    }

    public function test_the_form_renders_the_countdown_and_disables_the_button(): void
    {
        $user = User::factory()->create();
        $this->requestLinkFor($user->email);

        $page = $this->get('/forgot-password');

        $page->assertOk();
        $page->assertSee('You can ask for another in');
        $page->assertSee('x-bind:disabled="waiting"', false);
        // The countdown has to start from a real number, not a placeholder.
        $page->assertSee('remaining: '.session('retry_after'), false);
    }

    public function test_the_wait_is_measured_from_the_token_row(): void
    {
        $user = User::factory()->create();
        $this->requestLinkFor($user->email);

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);

        // Backdate the token: the countdown should follow the row, not the
        // moment of the request.
        DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->update(['created_at' => now()->subSeconds(50)]);

        $this->requestLinkFor($user->email);

        $this->assertEqualsWithDelta(10, session('retry_after'), 1);
    }
}
