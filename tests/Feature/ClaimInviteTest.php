<?php

namespace Tests\Feature;

use App\Models\ClaimInvite;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The claim-invite link is the only way an account is created on the live
 * site, so a token that should not work must not work: anyone holding one of
 * these URLs can create a login attached to a real family member.
 */
class ClaimInviteTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Invites are stored hashed, exactly as the controller looks them up.
     * The plaintext half is what travels in the emailed URL.
     *
     * @return array{0: Person, 1: ClaimInvite, 2: string}
     */
    private function inviteFor(Person $person, array $attributes = []): array
    {
        $plaintext = 'test-token-'.bin2hex(random_bytes(8));

        $invite = ClaimInvite::create(array_merge([
            'person_id' => $person->id,
            'token' => hash('sha256', $plaintext),
            'type' => 'adult_claim',
            'expires_at' => now()->addWeek(),
        ], $attributes));

        return [$person, $invite, $plaintext];
    }

    public function test_a_valid_token_shows_the_claim_page(): void
    {
        [$person, , $token] = $this->inviteFor(Person::factory()->create());

        $this->get("/claim/{$token}")
            ->assertOk()
            ->assertSee($person->full_name);
    }

    public function test_claiming_creates_an_account_and_links_it_to_the_person(): void
    {
        [$person, $invite, $token] = $this->inviteFor(Person::factory()->create());

        $response = $this->post("/claim/{$token}", [
            'email' => 'claimant@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        $user = User::where('email', 'claimant@example.com')->first();
        $this->assertNotNull($user);

        $person->refresh();
        $this->assertSame($user->id, $person->user_id);
        $this->assertSame('claimed', $person->claim_status);
        $this->assertNotNull($person->claimed_at);

        // Burned, so the same link cannot be used twice.
        $this->assertNotNull($invite->refresh()->used_at);
    }

    public function test_the_claimed_account_is_never_a_super_admin(): void
    {
        [, , $token] = $this->inviteFor(Person::factory()->create());

        $this->post("/claim/{$token}", [
            'email' => 'claimant@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ]);

        $this->assertFalse((bool) User::where('email', 'claimant@example.com')->first()->is_super_admin);
    }

    public function test_an_expired_token_is_rejected(): void
    {
        [, , $token] = $this->inviteFor(
            Person::factory()->create(),
            ['expires_at' => now()->subDay()],
        );

        $this->get("/claim/{$token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_already_used_token_is_rejected(): void
    {
        [, , $token] = $this->inviteFor(
            Person::factory()->create(),
            ['used_at' => now()->subHour()],
        );

        $this->get("/claim/{$token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_a_token_for_an_already_claimed_person_is_rejected(): void
    {
        [, , $token] = $this->inviteFor(Person::factory()->claimed()->create());

        $this->get("/claim/{$token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_an_unknown_token_is_rejected(): void
    {
        $this->inviteFor(Person::factory()->create());

        $this->get('/claim/not-a-real-token')->assertNotFound();
        $this->assertGuest();
    }

    /**
     * The database stores the hash. Presenting that hash instead of the
     * plaintext must not authenticate, or a leaked database read would be
     * enough to seize an account.
     */
    public function test_the_stored_hash_cannot_be_used_as_the_token(): void
    {
        [, $invite] = $this->inviteFor(Person::factory()->create());

        $this->get("/claim/{$invite->token}")->assertNotFound();
        $this->assertGuest();
    }

    public function test_claiming_requires_a_password_confirmation_that_matches(): void
    {
        [, , $token] = $this->inviteFor(Person::factory()->create());

        $this->post("/claim/{$token}", [
            'email' => 'claimant@example.com',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'something-else',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'claimant@example.com']);
    }

    public function test_a_failed_claim_does_not_burn_the_invite(): void
    {
        [, $invite, $token] = $this->inviteFor(Person::factory()->create());

        $this->post("/claim/{$token}", [
            'email' => 'not-an-email',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ])->assertSessionHasErrors('email');

        $this->assertNull($invite->refresh()->used_at);
    }
}
