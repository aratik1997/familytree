<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The portfolios index inside the admin area.
 *
 * It is a list of links rather than an editor — the eight pages are static
 * files built from the portfolios folder, not rows in this database — so what
 * matters is that it is reachable by the people who look after the tree, is
 * closed to everyone else, and names all eight.
 */
class PortfolioAdminTest extends TestCase
{
    use RefreshDatabase;

    private const EIGHT = ['ansary', 'ashik', 'morsheda', 'atik', 'maria', 'maimuna', 'anas', 'arafat'];

    public function test_a_moderator_can_open_the_portfolios_page(): void
    {
        $this->actingAs(User::factory()->create(['is_moderator' => true]))
            ->get('/admin/portfolios')
            ->assertOk()
            ->assertSee('Portfolios');
    }

    public function test_the_address_asked_for_by_name_works_too(): void
    {
        $this->actingAs(User::factory()->create(['is_super_admin' => true]))
            ->get('/portfolio/admin')
            ->assertOk()
            ->assertSee('Portfolios');
    }

    public function test_all_eight_are_listed_with_their_addresses(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_super_admin' => true]))
            ->get('/admin/portfolios');

        foreach (self::EIGHT as $slug) {
            $response->assertSee("https://{$slug}.khandanilegacy.com/");
        }
    }

    public function test_it_shows_the_full_names(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_super_admin' => true]))
            ->get('/admin/portfolios');

        $response->assertSee('Mohammed Abdur Rahman Ansary');
        $response->assertSee('Mosammat Morsheda Khatun');
        $response->assertSee('Mohammed Ahmadur Rahman Arafat');
    }

    public function test_an_ordinary_member_cannot_reach_it(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/portfolios')->assertForbidden();
        $this->actingAs($member)->get('/portfolio/admin')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get('/portfolio/admin')->assertRedirect('/login');
    }

    /**
     * The page has to be honest about the line between the two: wording is
     * editable here, anything structural still needs the project rebuilt.
     */
    public function test_it_says_what_still_needs_a_rebuild(): void
    {
        $this->actingAs(User::factory()->create(['is_super_admin' => true]))
            ->get('/admin/portfolios')
            ->assertSee('What needs a rebuild')
            ->assertSee('scripts/deploy.sh');
    }

    public function test_every_portfolio_offers_an_edit_link(): void
    {
        $response = $this->actingAs(User::factory()->create(['is_super_admin' => true]))
            ->get('/admin/portfolios');

        foreach (self::EIGHT as $slug) {
            $response->assertSee("/admin/portfolios/{$slug}/edit");
        }
    }
}
