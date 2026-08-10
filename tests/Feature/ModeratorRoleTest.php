<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A moderator looks after the family records — the same run of the tree the
 * Super Admin has, so the work of keeping it up to date can be shared.
 *
 * What stays with the Super Admin is not a longer reach into the records but a
 * different job: saying who the moderators are.
 */
class ModeratorRoleTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function moderator(): User
    {
        return User::factory()->create(['is_moderator' => true]);
    }

    public function test_a_moderator_reaches_the_admin_area(): void
    {
        $this->actingAs($this->moderator())->get('/admin')->assertOk();
    }

    /**
     * A moderator with their own record, a parent above them and a child and
     * grandchild below — the shape every rule below is measured against.
     *
     * @return array{0: User, 1: Person, 2: Person, 3: Person, 4: Person}
     */
    private function line(): array
    {
        $user = $this->moderator();

        $self = Person::factory()->create(['user_id' => $user->id, 'full_name' => 'The Moderator']);
        $parent = Person::factory()->create(['full_name' => 'Their Father']);
        $child = Person::factory()->create(['full_name' => 'Their Child']);
        $grandchild = Person::factory()->create(['full_name' => 'Their Grandchild']);

        $self->parents()->attach($parent->id, ['relationship_type' => 'biological']);
        $child->parents()->attach($self->id, ['relationship_type' => 'biological']);
        $grandchild->parents()->attach($child->id, ['relationship_type' => 'biological']);

        return [$user, $self, $parent, $child, $grandchild];
    }

    public function test_a_moderator_can_edit_their_own_record(): void
    {
        [$user, $self] = $this->line();

        $this->actingAs($user)->get("/people/{$self->id}/edit")->assertOk();
    }

    public function test_a_moderator_can_edit_the_generations_below_them(): void
    {
        [$user, , , $child, $grandchild] = $this->line();

        $this->actingAs($user)->get("/people/{$child->id}/edit")->assertOk();
        $this->actingAs($user)->get("/people/{$grandchild->id}/edit")->assertOk();
    }

    public function test_a_moderator_cannot_edit_the_generation_above_them(): void
    {
        [$user, , $parent] = $this->line();

        $this->actingAs($user)->get("/people/{$parent->id}/edit")->assertForbidden();
    }

    public function test_a_moderator_cannot_edit_someone_elses_branch(): void
    {
        [$user] = $this->line();
        $cousin = Person::factory()->create(['full_name' => 'A Cousin']);

        $this->actingAs($user)->get("/people/{$cousin->id}/edit")->assertForbidden();
    }

    public function test_a_moderator_can_change_who_is_related_to_whom_in_their_own_line(): void
    {
        [$user, , , $child] = $this->line();
        $newParent = Person::factory()->create();

        $this->actingAs($user)->postJson('/admin/relationships', [
            'child_id' => $child->id,
            'parent_id' => $newParent->id,
        ])->assertOk();

        $this->assertDatabaseHas('person_parent', [
            'child_id' => $child->id,
            'parent_id' => $newParent->id,
        ]);
    }

    public function test_a_moderator_cannot_reparent_somebody_outside_their_line(): void
    {
        [$user] = $this->line();
        $stranger = Person::factory()->create();
        $someoneElse = Person::factory()->create();

        $this->actingAs($user)->postJson('/admin/relationships', [
            'child_id' => $stranger->id,
            'parent_id' => $someoneElse->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('person_parent', [
            'child_id' => $stranger->id,
            'parent_id' => $someoneElse->id,
        ]);
    }

    public function test_the_super_admin_may_still_edit_anyone(): void
    {
        [, , $parent] = $this->line();

        $this->actingAs($this->superAdmin())->get("/people/{$parent->id}/edit")->assertOk();
    }

    /** A death is a fact about the family, not a change to somebody's profile. */
    public function test_a_moderator_can_record_a_death_for_any_generation(): void
    {
        [$user, , $parent] = $this->line();

        $this->actingAs($user)->patch("/people/{$parent->id}/deceased", [
            'is_deceased' => 1,
            'death_date' => '2026-01-15',
        ])->assertRedirect();

        $parent->refresh();

        $this->assertTrue($parent->is_deceased);
        $this->assertSame('2026-01-15', $parent->death_date->format('Y-m-d'));
    }

    public function test_recording_a_death_needs_no_date(): void
    {
        [$user, , $parent] = $this->line();

        $this->actingAs($user)->patch("/people/{$parent->id}/deceased", ['is_deceased' => 1]);

        $this->assertDatabaseHas('people', ['id' => $parent->id, 'is_deceased' => 1, 'death_date' => null]);
    }

    public function test_recording_a_death_changes_nothing_else(): void
    {
        [$user, , $parent] = $this->line();

        $this->actingAs($user)->patch("/people/{$parent->id}/deceased", [
            'is_deceased' => 1,
            'full_name' => 'Renamed By A Moderator',
        ]);

        $this->assertDatabaseHas('people', ['id' => $parent->id, 'full_name' => 'Their Father']);
    }

    public function test_an_ordinary_member_cannot_record_a_death(): void
    {
        $stranger = Person::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch("/people/{$stranger->id}/deceased", ['is_deceased' => 1])
            ->assertForbidden();

        $this->assertDatabaseHas('people', ['id' => $stranger->id, 'is_deceased' => 0]);
    }

    public function test_a_moderator_cannot_delete_anybody(): void
    {
        [$user, , , $child] = $this->line();

        // Correcting their own line is theirs; taking a person out of the
        // family altogether is the Super Admin's.
        $this->actingAs($user)->delete("/admin/people/{$child->id}")->assertForbidden();

        $this->assertDatabaseHas('people', ['id' => $child->id, 'deleted_at' => null]);
    }

    public function test_a_moderator_may_not_appoint_other_moderators(): void
    {
        $this->actingAs($this->moderator())->get('/admin/moderators')->assertForbidden();
    }

    public function test_a_moderator_may_not_read_the_mail_diagnostic(): void
    {
        // It reports how the server is configured, which is the Super Admin's.
        $this->actingAs($this->moderator())->get('/admin/mail-check')->assertForbidden();
    }

    public function test_an_ordinary_member_still_cannot_reach_the_admin_area(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_the_super_admin_can_appoint_a_moderator(): void
    {
        $member = User::factory()->create();
        Person::factory()->create(['user_id' => $member->id]);

        $this->actingAs($this->superAdmin())
            ->post("/admin/moderators/{$member->id}")
            ->assertRedirect();

        $this->assertTrue($member->fresh()->is_moderator);
    }

    public function test_the_super_admin_can_take_it_away_again(): void
    {
        $moderator = $this->moderator();
        Person::factory()->create(['user_id' => $moderator->id]);

        $this->actingAs($this->superAdmin())
            ->delete("/admin/moderators/{$moderator->id}")
            ->assertRedirect();

        $this->assertFalse($moderator->fresh()->is_moderator);
    }

    public function test_the_super_admins_own_role_is_not_editable_here(): void
    {
        $other = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($this->superAdmin())
            ->delete("/admin/moderators/{$other->id}")
            ->assertForbidden();

        $this->assertTrue($other->fresh()->is_super_admin);
    }

    public function test_the_list_offers_only_people_who_have_claimed_an_account(): void
    {
        $claimed = User::factory()->create();
        Person::factory()->create(['user_id' => $claimed->id, 'full_name' => 'Has An Account']);
        Person::factory()->create(['user_id' => null, 'full_name' => 'Never Claimed']);

        $response = $this->actingAs($this->superAdmin())->get('/admin/moderators');

        $response->assertSee('Has An Account');
        $response->assertDontSee('Never Claimed');
    }

    public function test_the_tree_page_lets_a_moderator_edit_it(): void
    {
        $this->actingAs($this->moderator())
            ->get('/tree')
            ->assertSee('CAN_MANAGE_TREE = true', false);
    }

    public function test_the_tree_page_stays_read_only_for_a_member(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/tree')
            ->assertSee('CAN_MANAGE_TREE = false', false);
    }
}
