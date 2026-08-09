<?php

namespace Tests\Feature;

use App\Models\ClaimInvite;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Sending an invitation emails the link and hands it back once for copying.
 * Both routes have to produce the same working link, and the link must still
 * be stored only as a hash.
 */
class InvitationLinkTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function unclaimedPerson(): Person
    {
        return Person::create([
            'full_name' => 'Zaria Ansary Ruhi',
            'email' => 'zaria@example.test',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(20)->format('Y-m-d'),
            'profile_photo_path' => 'profile-photos/none.png',
        ]);
    }

    public function test_sending_an_invitation_emails_it_and_returns_the_link_once(): void
    {
        Mail::fake();
        $person = $this->unclaimedPerson();

        $response = $this->actingAs($this->admin())
            ->post("/admin/people/{$person->id}/resend-invite");

        $response->assertSessionHas('status', 'invite-sent');
        $response->assertSessionHas('invite_email', $person->email);

        $link = session('invite_link');
        $this->assertStringContainsString('/claim/', $link);

        // Sent during the request, not queued: on a host with no worker a
        // queued invitation depends on QUEUE_CONNECTION staying "sync", and
        // Laravel's own default would leave it unsent with nothing to show.
        Mail::assertSent(\App\Mail\AccountClaimInvite::class);
    }

    public function test_the_returned_link_actually_opens_the_claim_page(): void
    {
        Mail::fake();
        $person = $this->unclaimedPerson();

        $this->actingAs($this->admin())->post("/admin/people/{$person->id}/resend-invite");
        $link = session('invite_link');

        // Signed out, exactly as the recipient would arrive.
        auth()->logout();

        $this->get($link)
            ->assertOk()
            ->assertSee($person->full_name);
    }

    public function test_only_the_hash_of_the_token_is_stored(): void
    {
        Mail::fake();
        $person = $this->unclaimedPerson();

        $this->actingAs($this->admin())->post("/admin/people/{$person->id}/resend-invite");

        $token = str($session = session('invite_link'))->afterLast('/')->toString();
        $stored = ClaimInvite::where('person_id', $person->id)->latest('id')->first();

        $this->assertNotSame($token, $stored->token);
        $this->assertSame(hash('sha256', $token), $stored->token);
    }

    public function test_sending_again_invalidates_the_previous_link(): void
    {
        Mail::fake();
        $person = $this->unclaimedPerson();
        $admin = $this->admin();

        $this->actingAs($admin)->post("/admin/people/{$person->id}/resend-invite");
        $firstLink = session('invite_link');

        $this->actingAs($admin)->post("/admin/people/{$person->id}/resend-invite");
        $secondLink = session('invite_link');

        $this->assertNotSame($firstLink, $secondLink);

        auth()->logout();
        $this->get($firstLink)->assertNotFound();
        $this->get($secondLink)->assertOk();
    }

    public function test_a_non_admin_cannot_send_invitations(): void
    {
        Mail::fake();
        $person = $this->unclaimedPerson();

        $this->actingAs(User::factory()->create(['is_super_admin' => false]))
            ->post("/admin/people/{$person->id}/resend-invite")
            ->assertForbidden();

        Mail::assertNothingQueued();
    }
}
