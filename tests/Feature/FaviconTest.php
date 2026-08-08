<?php

namespace Tests\Feature;

use App\Models\Tree;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * public/favicon.ico shipped as a 0-byte placeholder and no page ever asked for
 * an icon, so tabs showed the browser's blank sheet. These check the files are
 * real and that every layout points at them — including the error layout, which
 * builds its own <head> and would otherwise be the one page left bare.
 */
class FaviconTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_icon_files_exist_and_are_not_empty(): void
    {
        foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png'] as $file) {
            $path = public_path($file);

            $this->assertFileExists($path);
            $this->assertGreaterThan(500, filesize($path), "{$file} is suspiciously small");
        }
    }

    public function test_the_ico_really_is_an_icon_holding_three_sizes(): void
    {
        $data = file_get_contents(public_path('favicon.ico'));
        $header = unpack('vreserved/vtype/vcount', substr($data, 0, 6));

        $this->assertSame(0, $header['reserved']);
        $this->assertSame(1, $header['type'], 'type 1 is an icon');
        $this->assertSame(3, $header['count']);

        // Each entry must point at a real image inside the file, at the size it
        // claims — a wrong offset or length shows as a blank tab, not an error.
        $sizes = [];
        for ($i = 0; $i < $header['count']; $i++) {
            $entry = unpack('Cw/Ch/Ccolors/Cres/vplanes/vbpp/Vsize/Voffset', substr($data, 6 + 16 * $i, 16));
            $image = substr($data, $entry['offset'], $entry['size']);

            $this->assertSame($entry['size'], strlen($image), 'image data runs past the end of the file');

            [$width, $height] = getimagesizefromstring($image);
            $this->assertSame($entry['w'], $width);
            $this->assertSame($entry['h'], $height);

            $sizes[] = $entry['w'];
        }

        $this->assertSame([16, 32, 48], $sizes);
    }

    public function test_the_signed_in_pages_ask_for_the_icon(): void
    {
        $user = User::factory()->create(['tree_id' => Tree::factory()]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('favicon.svg', false);
        $response->assertSee('favicon.ico', false);
        $response->assertSee('apple-touch-icon.png', false);
    }

    public function test_the_sign_in_page_asks_for_the_icon(): void
    {
        $this->get('/login')->assertSee('favicon.svg', false);
    }

    public function test_the_error_pages_ask_for_the_icon_too(): void
    {
        $this->get('/no-such-page-exists')
            ->assertNotFound()
            ->assertSee('favicon.svg', false);
    }

    public function test_the_icon_is_versioned_so_the_empty_one_is_not_cached_for_ever(): void
    {
        $this->get('/login')->assertSee('favicon.ico?v=', false);
    }
}
