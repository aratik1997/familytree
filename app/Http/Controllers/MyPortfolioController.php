<?php

namespace App\Http\Controllers;

use App\Support\PortfolioContent;
use App\Support\PortfolioOwners;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A person editing their own portfolio, signed in with their family tree login.
 *
 * The slug is never taken from the request. It is resolved from who is signed
 * in, so there is no address anyone can type to reach somebody else's page —
 * the eight portfolios are one per person and the ownership is the identity.
 */
class MyPortfolioController extends Controller
{
    /** The portfolio belonging to whoever is signed in, or a 404. */
    private function ownSlug(Request $request): string
    {
        $slug = PortfolioOwners::slugFor($request->user());

        if ($slug === null) {
            // Not "forbidden": someone with no portfolio should not learn that
            // this address means anything at all.
            throw new NotFoundHttpException;
        }

        return $slug;
    }

    public function edit(Request $request)
    {
        $slug = $this->ownSlug($request);

        return view('portfolio.mine', [
            'slug' => $slug,
            'form' => PortfolioContent::formFor($slug),
            'url' => "https://{$slug}.khandanilegacy.com/",
        ]);
    }

    public function update(Request $request)
    {
        $slug = $this->ownSlug($request);

        $validated = $request->validate($this->rules($slug), [], $this->names());

        PortfolioContent::saveAll($slug, [
            'fields' => $validated['fields'] ?? [],
            'lists' => $validated['lists'] ?? [],
            'sections' => $validated['sections'] ?? [],
        ]);

        // Saved and published together. An admin edits several people at once
        // and wants a moment to check before any of it is live; a person
        // editing their own page has pressed the button that means "put this
        // on my website", and a second button to finish the job is a trap.
        $result = PortfolioContent::publish([$slug])[$slug];

        return redirect()
            ->route('my-portfolio.edit')
            ->with('status', 'portfolio-updated')
            ->with('publish_result', $result);
    }

    private function rules(string $slug): array
    {
        $rules = [];

        foreach (PortfolioContent::FIELDS as $field) {
            // A speech is a paragraph; a name is not.
            $max = in_array($field, ['tagline', 'speech'], true) ? 1200 : 160;
            foreach (['en', 'bn'] as $lang) {
                $rules["fields.$field.$lang"] = ['nullable', 'string', "max:$max"];
            }
        }

        // Capped at a length the layouts were drawn for. Beyond this a page
        // does not say more, it just breaks.
        $rules['lists'] = ['array'];

        $rules['lists.roles'] = ['array', 'max:12'];
        $rules['lists.roles.*.icon'] = ['nullable', 'string', 'max:8'];
        $rules['lists.roles.*.title.en'] = ['nullable', 'string', 'max:120'];
        $rules['lists.roles.*.title.bn'] = ['nullable', 'string', 'max:120'];
        $rules['lists.roles.*.org.en'] = ['nullable', 'string', 'max:160'];
        $rules['lists.roles.*.org.bn'] = ['nullable', 'string', 'max:160'];
        $rules['lists.roles.*.note.en'] = ['nullable', 'string', 'max:400'];
        $rules['lists.roles.*.note.bn'] = ['nullable', 'string', 'max:400'];

        $rules['lists.education'] = ['array', 'max:12'];
        $rules['lists.education.*.school'] = ['nullable', 'string', 'max:160'];
        $rules['lists.education.*.where.en'] = ['nullable', 'string', 'max:160'];
        $rules['lists.education.*.where.bn'] = ['nullable', 'string', 'max:160'];

        $rules['lists.languages'] = ['array', 'max:10'];
        $rules['lists.languages.*.name.en'] = ['nullable', 'string', 'max:60'];
        $rules['lists.languages.*.name.bn'] = ['nullable', 'string', 'max:60'];
        $rules['lists.languages.*.level.en'] = ['nullable', 'string', 'max:60'];
        $rules['lists.languages.*.level.bn'] = ['nullable', 'string', 'max:60'];
        $rules['lists.languages.*.v'] = ['nullable', 'integer', 'between:0,100'];

        $rules['lists.focus'] = ['array', 'max:16'];
        $rules['lists.focus.*.en'] = ['nullable', 'string', 'max:80'];
        $rules['lists.focus.*.bn'] = ['nullable', 'string', 'max:80'];

        // Only sections this page actually has. Anything else would be a row
        // saved against nothing, and Ordered would drop it on the next load.
        $keys = collect(PortfolioContent::sectionsFor($slug))->pluck('key')->all();
        $rules['sections'] = ['array', 'max:'.max(count($keys), 1)];
        $rules['sections.*.key'] = ['required', 'string', 'in:'.implode(',', $keys ?: ['none'])];
        $rules['sections.*.on'] = ['nullable', 'boolean'];

        return $rules;
    }

    /** Field names a person would recognise, for when validation complains. */
    private function names(): array
    {
        $names = [];
        foreach (['en' => 'English', 'bn' => 'Bangla'] as $lang => $label) {
            foreach (PortfolioContent::FIELDS as $field) {
                $names["fields.$field.$lang"] = __(ucfirst($field))." ({$label})";
            }
        }

        return $names;
    }
}
