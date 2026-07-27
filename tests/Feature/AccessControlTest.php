<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The tree holds living relatives' addresses, phone numbers and dates of
 * birth, so "who can reach which route" is the boundary that matters most.
 */
class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_sends_guests_to_the_login_screen(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_the_root_url_sends_signed_in_users_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    public static function guestProtectedRoutes(): array
    {
        return [
            'dashboard' => ['get', '/dashboard'],
            'tree' => ['get', '/tree'],
            'tree data' => ['get', '/tree/data'],
            'profile' => ['get', '/profile'],
        ];
    }

    #[DataProvider('guestProtectedRoutes')]
    public function test_guests_are_turned_away_from_protected_routes(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirect(route('login'));
    }

    /**
     * The person has to exist and be claimed, because route model binding
     * runs before the admin middleware and the action itself 404s for anyone
     * without a login. Otherwise this would pass on a 404 and prove nothing.
     */
    public function test_a_signed_in_user_may_not_reach_the_admin_area(): void
    {
        $person = Person::factory()->claimed()->create();

        $this->actingAs(User::factory()->create())
            ->get("/admin/people/{$person->id}/password")
            ->assertForbidden();
    }

    public function test_a_super_admin_may_reach_the_admin_area(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $person = Person::factory()->claimed()->create();

        $this->actingAs($admin)
            ->get("/admin/people/{$person->id}/password")
            ->assertOk();
    }

    /**
     * is_super_admin is not in the claim flow's input path, but it is
     * fillable — so confirm it cannot be set by simply posting it.
     */
    public function test_a_user_cannot_promote_themselves_via_the_profile_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch('/profile', [
            'name' => 'Renamed',
            'email' => $user->email,
            'is_super_admin' => true,
        ]);

        $this->assertFalse((bool) $user->refresh()->is_super_admin);
    }
}
