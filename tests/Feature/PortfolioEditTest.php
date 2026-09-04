<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\PortfolioContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Editing the portfolios' wording from the admin area.
 *
 * Saving records a change; publishing lays it beside each page as data.json
 * for the page to read. The two are separate so several edits go out together
 * and a half-typed sentence is never live.
 */
class PortfolioEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::delete(storage_path('app/portfolios.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('app/portfolios.json'));
        parent::tearDown();
    }

    private function admin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    public function test_the_edit_form_opens_filled_in(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin/portfolios/morsheda/edit')
            ->assertOk()
            ->assertSee('Mosammat Morsheda Khatun')
            ->assertSee('Dentist');
    }

    public function test_an_edit_is_saved(): void
    {
        $this->actingAs($this->admin())->patch('/admin/portfolios/atik', [
            'fields' => ['profession' => ['en' => 'Software Engineer', 'bn' => 'সফটওয়্যার প্রকৌশলী']],
        ])->assertRedirect(route('admin.portfolios.index'));

        $saved = PortfolioContent::for('atik');
        $this->assertSame('Software Engineer', $saved['profession']['en']);
        $this->assertSame('সফটওয়্যার প্রকৌশলী', $saved['profession']['bn']);
    }

    /** A blank box means "leave it alone", not "delete the line". */
    public function test_an_empty_field_is_not_stored_as_an_override(): void
    {
        $this->actingAs($this->admin())->patch('/admin/portfolios/atik', [
            'fields' => ['profession' => ['en' => 'Engineer', 'bn' => ''], 'speech' => ['en' => '', 'bn' => '']],
        ]);

        $saved = PortfolioContent::for('atik');
        $this->assertSame('Engineer', $saved['profession']['en']);
        $this->assertArrayNotHasKey('bn', $saved['profession']);
        $this->assertArrayNotHasKey('speech', $saved);
    }

    public function test_publishing_writes_a_data_file_beside_each_page(): void
    {
        $dir = PortfolioContent::pageDir('maria');
        if (! File::isDirectory($dir)) {
            $this->markTestSkipped('the built pages are not present in this checkout');
        }

        $this->actingAs($this->admin())->patch('/admin/portfolios/maria', [
            'fields' => ['profession' => ['en' => 'Architect and baker', 'bn' => '']],
        ]);

        $this->actingAs($this->admin())->post('/admin/portfolios/publish')->assertRedirect();

        $file = $dir.'/data.json';
        $this->assertFileExists($file);
        $this->assertSame('Architect and baker', json_decode(File::get($file), true)['profession']['en']);

        File::delete($file);
    }

    public function test_an_unknown_person_is_not_found(): void
    {
        $this->actingAs($this->admin())->get('/admin/portfolios/nobody/edit')->assertNotFound();
        $this->actingAs($this->admin())->patch('/admin/portfolios/nobody', ['fields' => []])->assertNotFound();
    }

    public function test_an_ordinary_member_cannot_edit_or_publish(): void
    {
        $member = User::factory()->create();

        $this->actingAs($member)->get('/admin/portfolios/atik/edit')->assertForbidden();
        $this->actingAs($member)->patch('/admin/portfolios/atik', ['fields' => []])->assertForbidden();
        $this->actingAs($member)->post('/admin/portfolios/publish')->assertForbidden();
    }

    public function test_a_moderator_may_edit(): void
    {
        $this->actingAs(User::factory()->create(['is_moderator' => true]))
            ->get('/admin/portfolios/anas/edit')
            ->assertOk();
    }

    public function test_an_over_long_name_is_rejected(): void
    {
        $this->actingAs($this->admin())->patch('/admin/portfolios/atik', [
            'fields' => ['name' => ['en' => str_repeat('a', 200), 'bn' => '']],
        ])->assertSessionHasErrors('fields.name.en');
    }
}
