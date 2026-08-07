<?php

namespace Tests\Feature;

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Adding a family link has always been possible; removing one had no way in at
 * all, so a link made by mistake stayed on the chart for good. These cover the
 * way back out — and that it stays shut to everyone but a Super Admin.
 */
class RelationshipRemovalTest extends TestCase
{
    use RefreshDatabase;

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

    private function person(string $name): Person
    {
        $this->admin();

        return Person::create([
            'full_name' => $name,
            'email' => str($name)->slug().'@example.test',
            'gender' => 'male',
            'date_of_birth' => '1950-01-01',
        ]);
    }

    public function test_a_super_admin_can_remove_a_wrongly_recorded_parent(): void
    {
        $son = $this->person('Atikur Rahman');
        $wrongParent = $this->person('Maria Khatun');

        $son->parents()->attach($wrongParent->id, ['relationship_type' => 'biological']);

        $this->actingAs($this->admin())
            ->delete("/admin/relationships/{$son->id}/{$wrongParent->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('person_parent', [
            'child_id' => $son->id,
            'parent_id' => $wrongParent->id,
        ]);
    }

    public function test_removing_a_link_leaves_both_people_in_the_record(): void
    {
        $son = $this->person('Atikur Rahman');
        $wrongParent = $this->person('Maria Khatun');

        $son->parents()->attach($wrongParent->id, ['relationship_type' => 'biological']);

        $this->actingAs($this->admin())
            ->delete("/admin/relationships/{$son->id}/{$wrongParent->id}");

        $this->assertDatabaseHas('people', ['id' => $son->id]);
        $this->assertDatabaseHas('people', ['id' => $wrongParent->id]);
    }

    public function test_only_the_named_link_goes(): void
    {
        $son = $this->person('Atikur Rahman');
        $father = $this->person('Mofizur Rahman');
        $wrongParent = $this->person('Maria Khatun');

        $son->parents()->attach($father->id, ['relationship_type' => 'biological']);
        $son->parents()->attach($wrongParent->id, ['relationship_type' => 'biological']);

        $this->actingAs($this->admin())
            ->delete("/admin/relationships/{$son->id}/{$wrongParent->id}");

        $this->assertDatabaseHas('person_parent', [
            'child_id' => $son->id,
            'parent_id' => $father->id,
        ]);
    }

    public function test_a_non_admin_cannot_remove_a_link(): void
    {
        $son = $this->person('Atikur Rahman');
        $parent = $this->person('Mofizur Rahman');
        $son->parents()->attach($parent->id, ['relationship_type' => 'biological']);

        $this->actingAs(User::factory()->create(['is_super_admin' => false]))
            ->delete("/admin/relationships/{$son->id}/{$parent->id}");

        $this->assertDatabaseHas('person_parent', [
            'child_id' => $son->id,
            'parent_id' => $parent->id,
        ]);
    }

    public function test_a_super_admin_can_remove_a_marriage_entered_by_mistake(): void
    {
        $husband = $this->person('Mofizur Rahman');
        $wife = $this->person('Ayesha Akter');

        $couple = Couple::create([
            'person_a_id' => $husband->id,
            'person_b_id' => $wife->id,
            'status' => 'married',
        ]);

        $this->actingAs($this->admin())
            ->delete("/couples/{$couple->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('couples', ['id' => $couple->id]);
    }

    public function test_the_edit_page_lists_parents_and_children_with_a_way_out(): void
    {
        $father = $this->person('Mofizur Rahman');
        $son = $this->person('Atikur Rahman');
        $wrongParent = $this->person('Maria Khatun');

        $son->parents()->attach($father->id, ['relationship_type' => 'biological']);
        $son->parents()->attach($wrongParent->id, ['relationship_type' => 'biological']);

        $response = $this->actingAs($this->admin())->get("/people/{$son->id}/edit");

        $response->assertOk();
        $response->assertSee('Family links');
        $response->assertSee('Maria Khatun');
        $response->assertSee('Mofizur Rahman');
        $response->assertSee("/admin/relationships/{$son->id}/{$wrongParent->id}");
    }
}
