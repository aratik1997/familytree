<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\User;
use App\Support\PortfolioContent;
use App\Support\PortfolioOwners;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * A person editing their own portfolio with their family tree login.
 *
 * The one thing worth guarding hardest: which page an edit lands on follows
 * from who is signed in and nothing else. There is no slug in the request, so
 * there is nothing to change that would reach somebody else's page.
 */
class MyPortfolioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The store is a file, not a table, so it does not roll back with the
        // database. Each test starts from nothing saved.
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

    /** A signed-in user who is the person a portfolio belongs to. */
    private function owner(string $fullName): User
    {
        $user = User::factory()->create();
        Person::factory()->create(['full_name' => $fullName, 'user_id' => $user->id]);
        PortfolioOwners::flush();

        return $user;
    }

    public function test_a_portfolio_owner_sees_their_own_editor(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->get(route('my-portfolio.edit'))
            ->assertOk()
            ->assertSee('Save and publish')
            ->assertSee('The order of the page');
    }

    public function test_the_dashboard_offers_the_editor_only_to_an_owner(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Edit portfolio');

        $this->actingAs($this->owner('Someone Entirely Else'))
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Edit portfolio');
    }

    public function test_the_address_does_not_exist_for_someone_without_a_portfolio(): void
    {
        $user = $this->owner('Someone Entirely Else');

        // Not "forbidden": they should not learn the address means anything.
        $this->actingAs($user)->get(route('my-portfolio.edit'))->assertNotFound();
        $this->actingAs($user)->patch(route('my-portfolio.update'), [])->assertNotFound();
    }

    public function test_a_login_with_no_person_in_the_tree_owns_nothing(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('my-portfolio.edit'))
            ->assertNotFound();
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('my-portfolio.edit'))->assertRedirect(route('login'));
    }

    public function test_an_edit_lands_on_the_owner_and_on_nobody_else(): void
    {
        $atik = $this->owner('Mohammed Atikur Rahman');
        $this->owner('Mosammat Morsheda Khatun');

        $this->actingAs($atik)->patch(route('my-portfolio.update'), [
            'fields' => ['tagline' => ['en' => 'Written by the owner.', 'bn' => '']],
        ])->assertRedirect(route('my-portfolio.edit'));

        $saved = PortfolioContent::all();

        $this->assertSame('Written by the owner.', $saved['atik']['fields']['tagline']['en']);
        $this->assertArrayNotHasKey('morsheda', $saved);
    }

    public function test_an_empty_box_keeps_what_the_page_already_says(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'fields' => ['tagline' => ['en' => '   ', 'bn' => '']],
        ]);

        // Nothing stored means nothing overridden, so the built line stands.
        $this->assertSame([], PortfolioContent::all()['atik']['fields']);
    }

    public function test_an_edited_list_replaces_the_built_one_so_a_row_can_be_removed(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'lists' => [
                'languages' => [
                    ['name' => ['en' => 'Bangla', 'bn' => 'বাংলা'], 'level' => ['en' => 'Native', 'bn' => 'মাতৃভাষা'], 'v' => 100],
                ],
            ],
        ]);

        $languages = PortfolioContent::all()['atik']['lists']['languages'];

        $this->assertCount(1, $languages);
        $this->assertSame('Bangla', $languages[0]['name']['en']);
    }

    public function test_rows_added_and_left_blank_are_dropped(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'lists' => [
                'focus' => [
                    ['en' => 'Laravel', 'bn' => 'লারাভেল'],
                    ['en' => '', 'bn' => ''],
                    ['en' => '  ', 'bn' => ''],
                ],
            ],
        ]);

        $this->assertSame(
            [['en' => 'Laravel', 'bn' => 'লারাভেল']],
            PortfolioContent::all()['atik']['lists']['focus']
        );
    }

    public function test_a_language_level_outside_the_range_is_refused(): void
    {
        // Refused rather than quietly bent into shape: a bar is a picture of a
        // number, and the person should be told their number was not kept.
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'lists' => ['languages' => [['name' => ['en' => 'Bangla', 'bn' => ''], 'level' => ['en' => '', 'bn' => ''], 'v' => 400]]],
        ])->assertSessionHasErrors('lists.languages.0.v');
    }

    public function test_the_order_and_the_hidden_parts_are_saved(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'sections' => [
                ['key' => 'languages', 'on' => '1'],
                ['key' => 'education', 'on' => '1'],
                ['key' => 'speech'],          // unticked: the checkbox sends nothing
                ['key' => 'focus', 'on' => '1'],
            ],
        ]);

        $this->assertSame([
            ['key' => 'languages', 'on' => true],
            ['key' => 'education', 'on' => true],
            ['key' => 'speech', 'on' => false],
            ['key' => 'focus', 'on' => true],
        ], PortfolioContent::all()['atik']['sections']);
    }

    public function test_a_section_the_page_does_not_have_is_refused(): void
    {
        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'sections' => [['key' => 'not-a-section', 'on' => '1']],
        ])->assertSessionHasErrors('sections.0.key');
    }

    public function test_saving_publishes_to_the_page_folder(): void
    {
        $dir = PortfolioContent::pageDir('atik');
        if (! File::isDirectory($dir)) {
            $this->markTestSkipped('atik has not been built on this machine');
        }

        File::delete($dir.'/data.json');

        $this->actingAs($this->owner('Mohammed Atikur Rahman'))->patch(route('my-portfolio.update'), [
            'fields' => ['tagline' => ['en' => 'Live straight away.', 'bn' => '']],
        ])->assertSessionHas('publish_result', fn ($r) => str_starts_with($r, 'published'));

        $published = json_decode(File::get($dir.'/data.json'), true);

        $this->assertSame('Live straight away.', $published['fields']['tagline']['en']);
    }

    public function test_the_form_opens_filled_in_with_what_the_page_already_says(): void
    {
        $form = PortfolioContent::formFor('atik');

        $this->assertSame('Mohammed Atikur Rahman', $form['fields']['name']['en']);
        $this->assertNotEmpty($form['lists']['languages']);
        $this->assertSame('speech', $form['sections'][0]['key']);
        $this->assertTrue($form['sections'][0]['on']);
    }

    public function test_a_section_the_page_gained_since_the_last_save_is_appended(): void
    {
        PortfolioContent::saveAll('atik', ['sections' => [['key' => 'languages', 'on' => true]]]);

        $sections = PortfolioContent::formFor('atik')['sections'];

        // Their choice leads; everything else follows, shown rather than lost.
        $this->assertSame('languages', $sections[0]['key']);
        $this->assertContains('speech', array_column($sections, 'key'));
        $this->assertSame([], array_filter($sections, fn ($s) => ! $s['on']));
    }

    public function test_an_admin_correcting_the_wording_leaves_the_owner_lists_alone(): void
    {
        PortfolioContent::saveAll('atik', [
            'lists' => ['focus' => [['en' => 'Laravel', 'bn' => 'লারাভেল']]],
            'sections' => [['key' => 'languages', 'on' => true]],
        ]);

        PortfolioContent::save('atik', ['tagline' => ['en' => 'Corrected by an admin.', 'bn' => '']]);

        $saved = PortfolioContent::all()['atik'];

        $this->assertSame('Corrected by an admin.', $saved['fields']['tagline']['en']);
        $this->assertCount(1, $saved['lists']['focus']);
        $this->assertCount(1, $saved['sections']);
    }
}
