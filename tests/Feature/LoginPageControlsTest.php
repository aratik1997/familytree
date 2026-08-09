<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The sign-in page is the one screen every relative meets, and the only one
 * some of them will see for a while. Both of these were missing from it because
 * they had only ever been built for the pages behind the login.
 */
class LoginPageControlsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_theme_can_be_switched_from_the_sign_in_page(): void
    {
        // The switch lived in the signed-in navigation, which nobody has
        // reached yet at this point.
        $this->get('/login')->assertSee('Switch to light theme', false);
    }

    public function test_the_theme_can_be_switched_on_the_other_doorway_pages(): void
    {
        foreach (['/forgot-password', '/register'] as $page) {
            $response = $this->get($page);

            if ($response->status() === 200) {
                $response->assertSee('Switch to light theme', false);
            }
        }

        // At least the sign-in page must have it, whatever else is enabled.
        $this->get('/login')->assertSee('Switch to light theme', false);
    }

    public function test_remember_me_uses_the_drawn_checkbox(): void
    {
        $response = $this->get('/login');

        $response->assertSee('name="remember"', false);
        $response->assertSee('class="checkbox"', false);
    }

    /**
     * The tick is a background-image on :checked, so any inline `background`
     * on the input — the shorthand resets background-image, and inline beats
     * the stylesheet — makes a checked box look exactly like an unchecked one.
     */
    public function test_the_checkbox_carries_no_inline_background(): void
    {
        $html = $this->get('/login')->getContent();

        preg_match('/<input[^>]*name="remember"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches, 'the remember-me input should be on the page');
        $this->assertStringNotContainsString('background', $matches[0]);
        $this->assertStringNotContainsString('style=', $matches[0]);
    }
}
