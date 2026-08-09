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

    public function test_a_moderator_can_add_and_edit_people(): void
    {
        $person = Person::factory()->create();

        $this->actingAs($this->moderator())->get('/admin/people/create')->assertOk();
        $this->actingAs($this->moderator())->get("/people/{$person->id}/edit")->assertOk();
    }

    public function test_a_moderator_can_change_who_is_related_to_whom(): void
    {
        $father = Person::factory()->create();
        $son = Person::factory()->create();

        $this->actingAs($this->moderator())->postJson('/admin/relationships', [
            'child_id' => $son->id,
            'parent_id' => $father->id,
        ])->assertOk();

        $this->assertDatabaseHas('person_parent', [
            'child_id' => $son->id,
            'parent_id' => $father->id,
        ]);
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
