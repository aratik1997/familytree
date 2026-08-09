<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Tree;
use App\Models\TreeMembership;
use App\Models\User;
use App\Notifications\TreeMembershipRequested;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Atikur Rahman is Mofizur Rahman's son in one tree and Abdul Mannan's
 * son-in-law in another. He is the same man in both, so his record is lent from
 * the tree it was entered in rather than copied — and nothing of him shows in
 * the second tree until he agrees to it.
 */
class CrossTreeMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    /** An admin, their tree, and a person in it who has claimed their account. */
    private function family(string $treeName, string $personName): array
    {
        $tree = Tree::factory()->create(['name' => $treeName]);
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => $tree->id]);

        $user = User::factory()->create(['tree_id' => $tree->id]);
        $person = Person::factory()->create([
            'tree_id' => $tree->id,
            'full_name' => $personName,
            'user_id' => $user->id,
        ]);

        return [$tree, $admin, $person, $user];
    }

    public function test_everybody_gets_an_id_to_be_found_by(): void
    {
        [, , $person] = $this->family('Khandani', 'Atikur Rahman');

        $this->assertNotNull($person->public_id);
        $this->assertStringStartsWith('FT-', $person->public_id);
    }

    public function test_two_people_never_share_an_id(): void
    {
        $tree = Tree::factory()->create();
        $codes = collect(range(1, 25))
            ->map(fn () => Person::factory()->create(['tree_id' => $tree->id])->public_id);

        $this->assertCount(25, $codes->unique());
    }

    public function test_asking_someone_in_by_their_id_creates_a_pending_request(): void
    {
        [, , $atikur] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', [
            'public_id' => $atikur->public_id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tree_memberships', [
            'person_id' => $atikur->id,
            'tree_id' => $mannan->tree_id,
            'status' => 'pending',
        ]);
    }

    public function test_the_person_asked_is_told(): void
    {
        [, , $atikur, $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);

        Notification::assertSentTo($atikurUser, TreeMembershipRequested::class);
    }

    /** The whole point: it must not show until he says yes. */
    public function test_a_pending_request_shows_nothing_in_the_asking_tree(): void
    {
        [, , $atikur] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);

        $this->actingAs($mannan)->getJson('/tree/data')->assertDontSee('Atikur Rahman');
        $this->actingAs($mannan)->get("/people/{$atikur->id}")->assertNotFound();
    }

    public function test_once_he_agrees_he_stands_in_both_trees(): void
    {
        [, , $atikur, $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [$manshafi, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);

        $membership = TreeMembership::where('person_id', $atikur->id)->firstOrFail();
        $this->actingAs($atikurUser)->post("/memberships/{$membership->id}/accept")->assertRedirect();

        $this->actingAs($mannan)->getJson('/tree/data')->assertSee('Atikur Rahman');
        $this->actingAs($mannan)->get("/people/{$atikur->id}")->assertOk();

        // And still in his own, which he never left.
        $this->assertSame($manshafi->id, $membership->fresh()->tree_id);
        $this->assertNotSame($manshafi->id, $atikur->fresh()->tree_id);
    }

    public function test_declining_leaves_him_out(): void
    {
        [, , $atikur, $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);

        $membership = TreeMembership::where('person_id', $atikur->id)->firstOrFail();
        $this->actingAs($atikurUser)->post("/memberships/{$membership->id}/decline");

        $this->actingAs($mannan)->getJson('/tree/data')->assertDontSee('Atikur Rahman');
    }

    public function test_only_the_person_asked_may_answer(): void
    {
        [, , $atikur] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);
        $membership = TreeMembership::where('person_id', $atikur->id)->firstOrFail();

        // Not even the family that asked can answer on his behalf.
        $this->actingAs($mannan)->post("/memberships/{$membership->id}/accept")->assertForbidden();

        $this->assertSame('pending', $membership->fresh()->status);
    }

    public function test_the_host_family_cannot_edit_a_guests_profile(): void
    {
        [, , $atikur, $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);
        $membership = TreeMembership::where('person_id', $atikur->id)->firstOrFail();
        $this->actingAs($atikurUser)->post("/memberships/{$membership->id}/accept");

        // Visible to them, but his name and photo stay his own family's business.
        $this->actingAs($mannan)->get("/people/{$atikur->id}")->assertOk();
        $this->actingAs($mannan)->get("/people/{$atikur->id}/edit")->assertForbidden();
    }

    public function test_he_can_look_at_the_tree_that_took_him_in(): void
    {
        [, , $atikur, $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [$manshafi, $mannan, $mannanPerson] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);
        $membership = TreeMembership::where('person_id', $atikur->id)->firstOrFail();
        $this->actingAs($atikurUser)->post("/memberships/{$membership->id}/accept");

        $this->actingAs($atikurUser)->get("/trees/{$manshafi->id}/switch")->assertRedirect();
        $this->actingAs($atikurUser)->getJson('/tree/data')->assertSee($mannanPerson->full_name);
    }

    public function test_he_cannot_switch_to_a_tree_that_never_asked_for_him(): void
    {
        [, , , $atikurUser] = $this->family('Khandani', 'Atikur Rahman');
        [$stranger] = $this->family('Strangers', 'Nobody Relevant');

        $this->actingAs($atikurUser)->get("/trees/{$stranger->id}/switch")->assertForbidden();
    }

    public function test_an_unknown_id_is_refused(): void
    {
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => 'FT-ZZZZZZ'])
            ->assertSessionHasErrors('public_id');
    }

    public function test_somebody_already_in_the_tree_is_refused(): void
    {
        [, $mannan, $mannanPerson] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $mannanPerson->public_id])
            ->assertSessionHasErrors('public_id');
    }

    public function test_the_same_person_cannot_be_asked_twice_over(): void
    {
        [, , $atikur] = $this->family('Khandani', 'Atikur Rahman');
        [, $mannan] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id]);
        $this->actingAs($mannan)->post('/memberships', ['public_id' => $atikur->public_id])
            ->assertSessionHasErrors('public_id');

        $this->assertSame(1, TreeMembership::where('person_id', $atikur->id)->count());
    }

    public function test_any_claimed_member_may_ask_not_only_an_admin(): void
    {
        [, , $atikur] = $this->family('Khandani', 'Atikur Rahman');
        [, , , $ordinaryMember] = $this->family('Manshafi', 'Abdul Mannan');

        $this->actingAs($ordinaryMember)->post('/memberships', [
            'public_id' => $atikur->public_id,
        ])->assertRedirect();

        $this->assertDatabaseHas('tree_memberships', ['person_id' => $atikur->id]);
    }
}
