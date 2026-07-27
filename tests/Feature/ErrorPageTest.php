<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * These are the pages nobody looks at until production is on fire, so they
 * are worth asserting: an error page that throws while rendering leaves the
 * visitor with a blank 500 and no way back.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unknown_url_renders_the_branded_404(): void
    {
        $this->get('/no-such-page-exists')
            ->assertNotFound()
            ->assertSee('ERROR 404')
            ->assertSee('This page is not in the record')
            ->assertSee('Return to the family tree');
    }

    public function test_error_pages_are_not_indexable(): void
    {
        $this->get('/no-such-page-exists')
            ->assertSee('noindex, nofollow', false);
    }

    /**
     * The whole point of the standalone layout: no Vite manifest lookup, so a
     * missing or stale public/build cannot turn an error into a second error.
     */
    public function test_the_error_page_does_not_depend_on_the_vite_manifest(): void
    {
        $this->get('/no-such-page-exists')
            ->assertDontSee('/build/assets/', false);
    }

    #[DataProvider('errorViews')]
    public function test_every_error_view_renders_on_its_own(string $view): void
    {
        $this->assertNotEmpty(trim(view($view)->render()));
    }

    /**
     * @return list<array{0: string}>
     */
    public static function errorViews(): array
    {
        return [
            ['errors.403'],
            ['errors.404'],
            ['errors.419'],
            ['errors.429'],
            ['errors.500'],
            ['errors.503'],
        ];
    }
}
