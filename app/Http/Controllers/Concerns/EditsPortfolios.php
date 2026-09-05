<?php

namespace App\Http\Controllers\Concerns;

use App\Support\PortfolioContent;
use App\Support\PortfolioPhoto;
use Illuminate\Http\Request;

/**
 * The rules and the saving for a portfolio page, shared by the person editing
 * their own and the admin editing anyone's.
 *
 * Shared rather than written twice on purpose: the two screens offer the same
 * powers over the same file, so a limit enforced on one and not the other
 * would be no limit at all.
 */
trait EditsPortfolios
{
    /** Everything a portfolio form may send, for one particular page. */
    protected function portfolioRules(string $slug): array
    {
        $rules = [];

        foreach (PortfolioContent::FIELDS as $field) {
            // A speech is a paragraph; a name is not.
            $max = in_array($field, ['tagline', 'speech'], true) ? 1200 : 160;
            foreach (['en', 'bn'] as $lang) {
                $rules["fields.$field.$lang"] = ['nullable', 'string', "max:$max"];
            }
        }

        // Capped at lengths the layouts were drawn for. Past this a page does
        // not say more, it just breaks.
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
        // saved against nothing, which Ordered drops on the next load anyway.
        $keys = collect(PortfolioContent::sectionsFor($slug))->pluck('key')->all();
        $rules['sections'] = ['array', 'max:'.max(count($keys), 1)];
        $rules['sections.*.key'] = ['required', 'string', 'in:'.implode(',', $keys ?: ['none'])];
        $rules['sections.*.on'] = ['nullable', 'boolean'];

        $rules['photo'] = ['nullable', 'image', 'mimetypes:'.implode(',', PortfolioPhoto::MIME_TYPES), 'max:8192'];
        $rules['remove_photo'] = ['nullable', 'boolean'];

        return $rules;
    }

    /** Writes the validated form to the store, photograph included. */
    protected function savePortfolio(Request $request, string $slug, array $validated): void
    {
        PortfolioContent::saveAll($slug, [
            'fields' => $validated['fields'] ?? [],
            'lists' => $validated['lists'] ?? [],
            'sections' => $validated['sections'] ?? [],
        ]);

        // A new picture wins over the tick box, so choosing a file and leaving
        // "remove" ticked from a previous visit does not throw the upload away.
        if ($request->hasFile('photo')) {
            PortfolioPhoto::put($slug, $request->file('photo'));
        } elseif ($request->boolean('remove_photo')) {
            PortfolioPhoto::delete($slug);
        }
    }

    /** What the editor needs to show the current picture, if there is one. */
    protected function photoView(string $slug): array
    {
        return [
            'hasPhoto' => PortfolioPhoto::exists($slug),
            // Addressed by the moment it was written, so replacing a photo
            // does not leave the browser showing the old one from its cache.
            'photoUrl' => PortfolioPhoto::exists($slug)
                ? route('portfolio.photo', ['slug' => $slug, 'v' => PortfolioPhoto::version($slug)])
                : null,
        ];
    }
}
