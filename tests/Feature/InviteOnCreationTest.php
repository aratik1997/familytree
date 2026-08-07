<?php

namespace Tests\Feature;

use App\Mail\AccountClaimInvite;
use App\Models\Person;
use App\Models\User;
use App\Support\ClaimInvites;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Adding somebody to the tree used to create their record and then say nothing
 * to them — an invitation only ever went out from the resend button or on an
 * 18th birthday. Everyone entered as an adult is now asked for their account
 * at the moment they are added.
 */
class InviteOnCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');
    }

    private ?User $admin = null;

    /** Signed in from the start, so people created here land in their tree. */
    private function admin(): User
    {
        if ($this->admin === null) {
            $this->admin = User::factory()->create(['is_super_admin' => true]);
            $this->actingAs($this->admin);
        }

        return $this->admin;
    }

    private function person(string $name, string $bornOn): Person
    {
        $this->admin();

        return Person::create([
            'full_name' => $name,
            'email' => str($name)->slug().'@example.test',
            'gender' => 'male',
            'date_of_birth' => $bornOn,
        ]);
    }

    private function photo(): UploadedFile
    {
        return UploadedFile::fake()->image('face.jpg');
    }

    public function test_adding_a_person_invites_them(): void
    {
        $root = $this->person('Mofizur Rahman', '1950-01-01');

        $this->actingAs($this->admin())->post('/admin/people', [
            'full_name' => 'Ayesha Akter',
            'email' => 'ayesha@example.test',
            'date_of_birth' => '1955-01-01',
            'gender' => 'female',
            'photo' => $this->photo(),
            'parent_selection' => (string) $root->id,
            'parent_relationship_type' => 'biological',
        ])->assertRedirect();

        Mail::assertSent(AccountClaimInvite::class);

        $this->assertDatabaseHas('people', [
            'email' => 'ayesha@example.test',
            'claim_status' => 'pending_invite',
        ]);
    }

    public function test_the_new_person_gets_a_live_token(): void
    {
        $root = $this->person('Mofizur Rahman', '1950-01-01');

        $this->actingAs($this->admin())->post('/admin/people', [
            'full_name' => 'Ayesha Akter',
            'email' => 'ayesha@example.test',
            'date_of_birth' => '1955-01-01',
            'gender' => 'female',
            'photo' => $this->photo(),
            'parent_selection' => (string) $root->id,
            'parent_relationship_type' => 'biological',
        ]);

        $person = Person::where('email', 'ayesha@example.test')->firstOrFail();

        $this->assertDatabaseHas('claim_invites', [
            'person_id' => $person->id,
            'used_at' => null,
        ]);
    }

    public function test_a_child_under_eighteen_is_not_invited(): void
    {
        $parent = $this->person('Mofizur Rahman', '1950-01-01');

        $this->actingAs($this->admin())->post("/people/{$parent->id}/children", [
            'mode' => 'new',
            'full_name' => 'Young Child',
            'email' => 'young@example.test',
            'date_of_birth' => now()->subYears(9)->format('Y-m-d'),
            'gender' => 'male',
            'relationship_type' => 'biological',
            'photo' => $this->photo(),
        ])->assertRedirect();

        // Their profile belongs to their parent until they turn 18, and the
        // adult-transition job invites them on the day.
        Mail::assertNothingSent();
    }

    public function test_an_adult_child_is_invited(): void
    {
        $parent = $this->person('Mofizur Rahman', '1950-01-01');

        $this->actingAs($this->admin())->post("/people/{$parent->id}/children", [
            'mode' => 'new',
            'full_name' => 'Atikur Rahman',
            'email' => 'atikur@example.test',
            'date_of_birth' => now()->subYears(28)->format('Y-m-d'),
            'gender' => 'male',
            'relationship_type' => 'biological',
            'photo' => $this->photo(),
        ])->assertRedirect();

        Mail::assertSent(AccountClaimInvite::class);
    }

    public function test_somebody_already_claimed_is_never_re_invited(): void
    {
        $person = $this->person('Mofizur Rahman', '1950-01-01');
        $person->update(['user_id' => User::factory()->create()->id]);

        $this->assertFalse(ClaimInvites::eligible($person->fresh()));
    }

    public function test_somebody_without_an_address_is_not_invited(): void
    {
        // Not saved: the column is NOT NULL, so this state only ever arises in
        // memory — but nothing may try to email an empty address if it does.
        $person = new Person([
            'full_name' => 'No Address',
            'email' => null,
            'gender' => 'male',
            'date_of_birth' => '1950-01-01',
        ]);

        $this->assertFalse(ClaimInvites::eligible($person));
        $this->assertNull(ClaimInvites::send($person));
        Mail::assertNothingSent();
    }
}
