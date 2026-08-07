<?php

namespace Tests\Feature;

use App\Models\Couple;
use App\Models\Person;
use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * One family must never see another's records.
 *
 * The whole arrangement rests on that, so it is checked from several directions
 * rather than once: the tree endpoint, the profile pages, the admin list, and
 * the private fields. An Admin has every power the Super Admin has inside their
 * own tree, and none whatsoever outside it — the Super Admin included.
 */
class TreeIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    /** An Admin with a tree of their own and one person in it. */
    private function adminWithFamily(string $name): array
    {
        $admin = User::factory()->create(['is_admin' => true, 'tree_id' => Tree::factory()]);

        $person = Person::factory()->create([
            'tree_id' => $admin->tree_id,
            'full_name' => $name,
            'mobile' => '01700000000',
        ]);

        return [$admin, $person];
    }

    public function test_the_tree_endpoint_returns_only_your_own_family(): void
    {
        [$admin, $mine] = $this->adminWithFamily('My Own Relative');
        [, $theirs] = $this->adminWithFamily('Somebody Elses Relative');

        $response = $this->actingAs($admin)->getJson('/tree/data');

        $response->assertOk();
        $response->assertSee('My Own Relative');
        $response->assertDontSee('Somebody Elses Relative');
        $this->assertNotNull($mine);
        $this->assertNotNull($theirs);
    }

    public function test_another_familys_profile_page_is_not_found(): void
    {
        [$admin] = $this->adminWithFamily('Mine');
        [, $stranger] = $this->adminWithFamily('Theirs');

        $this->actingAs($admin)->get("/people/{$stranger->id}")->assertNotFound();
    }

    public function test_another_familys_profile_cannot_be_edited(): void
    {
        [$admin] = $this->adminWithFamily('Mine');
        [, $stranger] = $this->adminWithFamily('Theirs');

        $this->actingAs($admin)->get("/people/{$stranger->id}/edit")->assertNotFound();
        $this->actingAs($admin)->patch("/people/{$stranger->id}", [
            'full_name' => 'Renamed By An Outsider',
        ])->assertNotFound();

        $this->assertDatabaseHas('people', ['id' => $stranger->id, 'full_name' => 'Theirs']);
    }

    public function test_the_admin_list_shows_only_your_own_family(): void
    {
        [$admin] = $this->adminWithFamily('My Own Relative');
        $this->adminWithFamily('Somebody Elses Relative');

        $response = $this->actingAs($admin)->get('/admin');

        $response->assertOk();
        $response->assertSee('My Own Relative');
        $response->assertDontSee('Somebody Elses Relative');
    }

    public function test_the_super_admin_cannot_see_an_admins_tree_either(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'tree_id' => Tree::factory()]);
        [, $stranger] = $this->adminWithFamily('An Admins Relative');

        $this->actingAs($superAdmin)->get('/admin')->assertDontSee('An Admins Relative');
        $this->actingAs($superAdmin)->get("/people/{$stranger->id}")->assertNotFound();
    }

    public function test_a_person_added_by_an_admin_lands_in_their_own_tree(): void
    {
        [$admin, $root] = $this->adminWithFamily('Root Person');

        $this->actingAs($admin)->post('/admin/people', [
            'full_name' => 'Newly Added',
            'email' => 'newly@example.test',
            'date_of_birth' => '1980-01-01',
            'gender' => 'male',
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('f.jpg'),
            'parent_selection' => (string) $root->id,
            'parent_relationship_type' => 'biological',
        ])->assertRedirect();

        $this->assertDatabaseHas('people', [
            'full_name' => 'Newly Added',
            'tree_id' => $admin->tree_id,
        ]);
    }

    public function test_a_couple_is_stamped_with_the_tree_it_was_made_in(): void
    {
        [$admin, $husband] = $this->adminWithFamily('Husband');
        $wife = Person::factory()->create(['tree_id' => $admin->tree_id]);

        $this->actingAs($admin);

        $couple = Couple::create([
            'person_a_id' => $husband->id,
            'person_b_id' => $wife->id,
            'status' => 'married',
        ]);

        $this->assertSame($admin->tree_id, $couple->tree_id);
    }

    public function test_an_admin_has_the_same_powers_as_a_super_admin_in_their_own_tree(): void
    {
        [$admin, $person] = $this->adminWithFamily('Their Relative');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get("/people/{$person->id}/edit")->assertOk();
        $this->actingAs($admin)->get('/admin/people/create')->assertOk();
    }

    public function test_only_the_super_admin_may_manage_admins(): void
    {
        [$admin] = $this->adminWithFamily('Whoever');
        $superAdmin = User::factory()->create(['is_super_admin' => true, 'tree_id' => Tree::factory()]);

        $this->actingAs($admin)->get('/admin/admins')->assertForbidden();
        $this->actingAs($superAdmin)->get('/admin/admins')->assertOk();
    }

    /**
     * The moment a relative claims their account is where the tree can most
     * easily be lost: a new login is made for them, and if it is not tied to
     * the family they were invited into they sign in successfully to an empty
     * site, every one of their own relatives filtered out of every query.
     */
    public function test_claiming_an_account_puts_the_new_login_in_that_family(): void
    {
        [$admin, ] = $this->adminWithFamily('Head Of Family');

        $person = Person::factory()->create([
            'tree_id' => $admin->tree_id,
            'email' => 'newcomer@example.test',
        ]);

        $token = str_repeat('a', 64);
        $person->invites()->create([
            'token' => hash('sha256', $token),
            'type' => 'manual_invite',
            'expires_at' => now()->addDays(7),
        ]);

        auth()->logout();

        $this->post("/claim/{$token}", [
            'email' => 'newcomer@example.test',
            'password' => 'a-long-enough-password',
            'password_confirmation' => 'a-long-enough-password',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'newcomer@example.test')->firstOrFail();

        $this->assertSame($admin->tree_id, $user->tree_id);
        $this->assertFalse($user->managesTree());

        // And they can actually see their own family.
        $this->actingAs($user)->get('/tree/data')->assertSee('Head Of Family');
    }
}
