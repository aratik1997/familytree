<?php

namespace Tests\Feature;

use App\Mail\AdminInvite;
use App\Models\Person;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Saying who the Admins are is the Super Admin's one exclusive job. Creating
 * one opens a family tree of their own, empty, for them to build.
 */
class AdminAccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true, 'tree_id' => Tree::factory()]);
    }

    public function test_creating_an_admin_gives_them_their_own_empty_tree(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/admins', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.test',
        ])->assertRedirect(route('admin.admins.index'));

        $admin = User::where('email', 'rahim@example.test')->firstOrFail();

        $this->assertTrue($admin->is_admin);
        $this->assertFalse($admin->is_super_admin);
        $this->assertNotNull($admin->tree_id);

        // Empty, as agreed: the first person in it is theirs to add.
        $this->assertSame(0, Person::withoutGlobalScopes()->where('tree_id', $admin->tree_id)->count());
    }

    public function test_the_new_admin_is_emailed_a_way_in(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/admins', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.test',
        ]);

        Mail::assertSent(AdminInvite::class);
    }

    public function test_their_tree_can_be_named(): void
    {
        $this->actingAs($this->superAdmin())->post('/admin/admins', [
            'name' => 'Rahim Uddin',
            'email' => 'rahim@example.test',
            'tree_name' => 'The Uddin Family',
        ]);

        $this->assertDatabaseHas('trees', ['name' => 'The Uddin Family']);
    }

    public function test_an_admin_may_not_create_other_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => Tree::factory()]);

        $this->actingAs($admin)->post('/admin/admins', [
            'name' => 'Someone New',
            'email' => 'new@example.test',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'new@example.test']);
    }

    public function test_an_admin_can_be_renamed_without_touching_their_tree_contents(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => Tree::factory()]);
        $person = Person::factory()->create(['tree_id' => $admin->tree_id]);

        $this->actingAs($this->superAdmin())->patch("/admin/admins/{$admin->id}", [
            'name' => 'New Name',
            'email' => $admin->email,
            'tree_name' => 'Renamed Tree',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'name' => 'New Name']);
        $this->assertDatabaseHas('trees', ['id' => $admin->tree_id, 'name' => 'Renamed Tree']);
        $this->assertDatabaseHas('people', ['id' => $person->id]);
    }

    public function test_removing_an_admin_takes_their_tree_with_them(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => Tree::factory()]);
        $person = Person::factory()->create(['tree_id' => $admin->tree_id]);

        $this->actingAs($this->superAdmin())
            ->delete("/admin/admins/{$admin->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('trees', ['id' => $admin->tree_id]);

        // Their family's records existed only inside that tree, and nobody
        // else could ever see them, so there is nowhere for them to go.
        $this->assertDatabaseMissing('people', ['id' => $person->id]);
    }

    public function test_the_invitation_can_be_sent_again(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => Tree::factory()]);

        $this->actingAs($this->superAdmin())
            ->post("/admin/admins/{$admin->id}/resend-invite")
            ->assertRedirect();

        Mail::assertSent(AdminInvite::class);
    }

    public function test_an_email_already_in_use_is_refused(): void
    {
        $existing = User::factory()->create();

        $this->actingAs($this->superAdmin())->post('/admin/admins', [
            'name' => 'Rahim Uddin',
            'email' => $existing->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_an_ordinary_member_cannot_reach_the_admin_list(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/admins')
            ->assertForbidden();
    }
}
