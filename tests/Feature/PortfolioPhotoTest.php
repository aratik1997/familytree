<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use App\Support\PortfolioContent;
use App\Support\PortfolioOwners;
use App\Support\PortfolioPhoto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * The portrait on a portfolio page.
 *
 * Kept apart from the family tree's own pictures: uploading here changes the
 * website and nothing else. The stored original is not in the web root, so it
 * is readable only by the person whose page it is, or by an admin.
 */
class PortfolioPhotoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::delete(storage_path('app/portfolios.json'));
        File::deleteDirectory(storage_path('app/portfolio-photos'));
        PortfolioOwners::flush();
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/portfolios.json'));
        File::deleteDirectory(storage_path('app/portfolio-photos'));
        PortfolioOwners::flush();
        parent::tearDown();
    }

    private function owner(string $fullName): User
    {
        $user = User::factory()->create();
        Person::factory()->create(['full_name' => $fullName, 'user_id' => $user->id]);
        PortfolioOwners::flush();

        return $user;
    }

    private function admin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    public function test_an_owner_can_upload_their_portrait(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->patch(route('my-portfolio.update'), [
                'photo' => UploadedFile::fake()->image('me.jpg', 900, 1200),
            ])
            ->assertRedirect(route('my-portfolio.edit'));

        $this->assertTrue(PortfolioPhoto::exists('atik'));

        // Written out by GD as a plain JPEG, whatever arrived.
        $this->assertSame('image/jpeg', mime_content_type(PortfolioPhoto::path('atik')));
    }

    public function test_an_oversized_photo_is_brought_down_to_a_size_a_page_can_load(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->patch(route('my-portfolio.update'), [
                'photo' => UploadedFile::fake()->image('huge.jpg', 4000, 3000),
            ]);

        [$w, $h] = getimagesize(PortfolioPhoto::path('atik'));

        $this->assertSame(1200, max($w, $h));
        $this->assertSame(900, min($w, $h), 'the shape of the picture should be kept');
    }

    public function test_a_file_that_is_not_a_picture_is_refused(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->patch(route('my-portfolio.update'), [
                'photo' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ])
            ->assertSessionHasErrors('photo');

        $this->assertFalse(PortfolioPhoto::exists('atik'));
    }

    public function test_a_photo_can_be_removed(): void
    {
        $user = $this->owner('Mohammed Atikur Rahman');

        $this->actingAs($user)->patch(route('my-portfolio.update'), [
            'photo' => UploadedFile::fake()->image('me.jpg', 600, 800),
        ]);
        $this->assertTrue(PortfolioPhoto::exists('atik'));

        $this->actingAs($user)->patch(route('my-portfolio.update'), ['remove_photo' => '1']);
        $this->assertFalse(PortfolioPhoto::exists('atik'));
    }

    public function test_choosing_a_file_wins_over_a_remove_tick(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->patch(route('my-portfolio.update'), [
                'photo' => UploadedFile::fake()->image('me.jpg', 600, 800),
                'remove_photo' => '1',
            ]);

        $this->assertTrue(PortfolioPhoto::exists('atik'));
    }

    public function test_saving_lays_the_photo_beside_the_page(): void
    {
        $dir = PortfolioContent::pageDir('atik');
        if (! File::isDirectory($dir)) {
            $this->markTestSkipped('atik has not been built on this machine');
        }

        $published = $dir.'/img/atik.jpg';
        $before = File::exists($published) ? File::get($published) : null;

        try {
            $this->actingAs($this->owner('Mohammed Atikur Rahman'))
                ->patch(route('my-portfolio.update'), [
                    'photo' => UploadedFile::fake()->image('me.jpg', 600, 800),
                ])
                ->assertSessionHas('publish_result', 'published, with the photo');

            // The page asks for img/<slug>.jpg beside itself; that is the file.
            $this->assertFileExists($published);
            $this->assertSame(
                File::get(PortfolioPhoto::path('atik')),
                File::get($published)
            );
        } finally {
            $before === null ? File::delete($published) : File::put($published, $before);
        }
    }

    public function test_the_stored_photo_is_served_only_to_those_who_may_edit_it(): void
    {
        $atik = $this->owner('Mohammed Atikur Rahman');
        $this->actingAs($atik)->patch(route('my-portfolio.update'), [
            'photo' => UploadedFile::fake()->image('me.jpg', 400, 500),
        ]);

        $url = route('portfolio.photo', ['slug' => 'atik']);

        $this->actingAs($atik)->get($url)->assertOk()->assertHeader('content-type', 'image/jpeg');
        $this->actingAs($this->admin())->get($url)->assertOk();

        // Somebody else's page is not theirs to look at before it is published.
        $this->actingAs($this->owner('Mosammat Morsheda Khatun'))->get($url)->assertNotFound();
    }

    public function test_a_guest_is_sent_to_sign_in_for_a_stored_photo(): void
    {
        // Written straight to the store rather than through the form, because
        // actingAs would stay in force for the rest of the test and there
        // would be no guest left to check.
        PortfolioPhoto::put('atik', UploadedFile::fake()->image('me.jpg', 400, 500));

        $this->get(route('portfolio.photo', ['slug' => 'atik']))->assertRedirect(route('login'));
    }

    public function test_the_address_changes_when_the_photo_does(): void
    {
        $user = $this->owner('Mohammed Atikur Rahman');

        $this->actingAs($user)->patch(route('my-portfolio.update'), [
            'photo' => UploadedFile::fake()->image('one.jpg', 400, 500),
        ]);
        $first = PortfolioPhoto::version('atik');

        touch(PortfolioPhoto::path('atik'), time() + 60);
        clearstatcache();

        // The editor addresses the picture by when it was written, so a
        // replacement is a new address and no browser can show the old one.
        $this->assertNotSame($first, PortfolioPhoto::version('atik'));
    }

    public function test_an_admin_can_set_the_photo_for_any_of_the_eight(): void
    {
        $this->actingAs($this->admin())
            ->patch('/admin/portfolios/morsheda', [
                'photo' => UploadedFile::fake()->image('her.jpg', 600, 800),
            ])
            ->assertRedirect(route('admin.portfolios.index'));

        $this->assertTrue(PortfolioPhoto::exists('morsheda'));
    }

    public function test_an_ordinary_member_cannot_set_anyone_photo(): void
    {
        $this->actingAs(User::factory()->create())
            ->patch('/admin/portfolios/morsheda', [
                'photo' => UploadedFile::fake()->image('her.jpg', 600, 800),
            ])
            ->assertForbidden();

        $this->assertFalse(PortfolioPhoto::exists('morsheda'));
    }
}
