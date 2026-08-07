<?php

namespace Tests\Feature;

use App\Models\Couple;
use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Linking one person as another's child is the single gesture that can make the
 * family chart undrawable, because it is the only one that can contradict a
 * fact already on record. Both contradictions are refused here rather than
 * being written down and left for the layout to reconcile — it cannot.
 */
class RelationshipGuardTest extends TestCase
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

    public function test_a_spouse_cannot_also_be_recorded_as_their_partners_child(): void
    {
        $husband = $this->person('Mofizur Rahman');
        $wife = $this->person('Ayesha Akter');

        Couple::create([
            'person_a_id' => $husband->id,
            'person_b_id' => $wife->id,
            'status' => 'married',
        ]);

        $response = $this->actingAs($this->admin())->postJson('/admin/relationships', [
            'child_id' => $wife->id,
            'parent_id' => $husband->id,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('person_parent', [
            'child_id' => $wife->id,
            'parent_id' => $husband->id,
        ]);
    }

    public function test_it_refuses_whichever_way_round_the_couple_was_recorded(): void
    {
        $husband = $this->person('Mofizur Rahman');
        $wife = $this->person('Ayesha Akter');

        // Stored with the wife on the A side this time — the guard reads both.
        Couple::create([
            'person_a_id' => $wife->id,
            'person_b_id' => $husband->id,
            'status' => 'married',
        ]);

        $this->actingAs($this->admin())->postJson('/admin/relationships', [
            'child_id' => $wife->id,
            'parent_id' => $husband->id,
        ])->assertStatus(422);
    }

    public function test_a_divorced_couple_is_still_refused(): void
    {
        $husband = $this->person('Mofizur Rahman');
        $wife = $this->person('Ayesha Akter');

        Couple::create([
            'person_a_id' => $husband->id,
            'person_b_id' => $wife->id,
            'status' => 'divorced',
        ]);

        $this->actingAs($this->admin())->postJson('/admin/relationships', [
            'child_id' => $wife->id,
            'parent_id' => $husband->id,
        ])->assertStatus(422);
    }

    public function test_an_ordinary_parent_link_is_still_written(): void
    {
        $father = $this->person('Mofizur Rahman');
        $son = $this->person('Atikur Rahman');

        $this->actingAs($this->admin())->postJson('/admin/relationships', [
            'child_id' => $son->id,
            'parent_id' => $father->id,
        ])->assertOk();

        $this->assertDatabaseHas('person_parent', [
            'child_id' => $son->id,
            'parent_id' => $father->id,
        ]);
    }

    public function test_a_cycle_is_still_refused(): void
    {
        $father = $this->person('Mofizur Rahman');
        $son = $this->person('Atikur Rahman');

        $son->parents()->attach($father->id, ['relationship_type' => 'biological']);

        $this->actingAs($this->admin())->postJson('/admin/relationships', [
            'child_id' => $father->id,
            'parent_id' => $son->id,
        ])->assertStatus(422);
    }
}
