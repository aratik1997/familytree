<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The language belongs to the visitor's session rather than their account, so
 * that it works on the sign-in and claim pages — the screens the family meet
 * before they have an account at all, and where Bangla matters most.
 */
class LanguageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_switch_to_bangla_before_signing_in(): void
    {
        $this->from('/login')->get('/locale/bn')->assertRedirect('/login');

        $this->assertSame('bn', session('locale'));
    }

    public function test_the_sign_in_page_comes_back_in_bangla(): void
    {
        $this->get('/locale/bn');

        $this->get('/login')->assertSee('লগইন');
    }

    public function test_the_choice_survives_from_one_page_to_the_next(): void
    {
        $this->get('/locale/bn');

        $this->get('/login')->assertOk();
        $this->get('/forgot-password')->assertSee('পাসওয়ার্ড', false);
    }

    public function test_it_can_be_switched_back_to_english(): void
    {
        $this->get('/locale/bn');
        $this->get('/locale/en');

        $this->assertSame('en', session('locale'));
        $this->get('/login')->assertSee('Log in');
    }

    public function test_a_language_we_do_not_have_is_refused(): void
    {
        $this->get('/locale/fr')->assertNotFound();

        $this->assertNull(session('locale'));
    }

    public function test_a_signed_in_member_sees_the_tree_in_bangla(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/locale/bn');

        $this->get('/tree')->assertSee('বংশতালিকা');
    }

    public function test_english_stays_the_default(): void
    {
        $this->get('/login')->assertSee('Log in');
    }
}
