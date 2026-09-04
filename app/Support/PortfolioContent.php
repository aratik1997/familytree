<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * The editable words of the eight portfolio pages.
 *
 * Held as a JSON file rather than a table on purpose: this host has no shell,
 * so a migration is a thing somebody has to paste into phpMyAdmin, and that is
 * a poor trade for eight rows of text. The file is small, versionable and
 * needs nothing run to start working.
 *
 * The built pages still carry their own copy of every word, so a page renders
 * correctly whether or not this file exists. What publishing does is lay a
 * data.json beside each page for it to read at load — inside the page's own
 * folder, because each portfolio is its own subdomain and anything fetched
 * from the main site would be blocked as cross-origin.
 */
class PortfolioContent
{
    /** Only what is worth correcting by hand — the prose, in both languages. */
    public const FIELDS = ['name', 'called', 'role', 'profession', 'tagline', 'speech'];

    private static function path(): string
    {
        return storage_path('app/portfolios.json');
    }

    /** Everything on record, keyed by slug. Empty until something is saved. */
    public static function all(): array
    {
        if (! File::exists(static::path())) {
            return [];
        }

        return json_decode(File::get(static::path()), true) ?: [];
    }

    /** One person's overrides, or an empty set. */
    public static function for(string $slug): array
    {
        return static::all()[$slug] ?? [];
    }

    /**
     * What the edit form should show: whatever has been saved, falling back to
     * what the built page already says, so the form is never blank.
     */
    public static function withDefaults(string $slug, array $defaults): array
    {
        $saved = static::for($slug);
        $fields = $saved['fields'] ?? $saved;   // 'fields' since lists were added; flat before that
        $out = [];

        foreach (static::FIELDS as $field) {
            foreach (['en', 'bn'] as $lang) {
                $out[$field][$lang] = $fields[$field][$lang] ?? ($defaults[$field][$lang] ?? '');
            }
        }

        return $out;
    }

    /** Saves just the wording — the admin form's half of the record. */
    public static function save(string $slug, array $values): void
    {
        // Routed through saveAll so an admin correcting a tagline leaves the
        // owner's own lists and section order exactly where they were.
        static::saveAll($slug, ['fields' => $values]);
    }

    /** The lists an owner can add to, edit, reorder and remove. */
    public const LISTS = ['roles', 'education', 'languages', 'focus'];

    /**
     * The sections each page has, in the order it was built with.
     *
     * Read from the portfolios project rather than repeated here: the pages
     * are the authority on what sections they contain, and a second list would
     * be free to fall out of step with them.
     */
    public static function sectionsFor(string $slug): array
    {
        $file = base_path('portfolios/src/sections.json');

        if (! File::exists($file)) {
            return [];
        }

        return json_decode(File::get($file), true)[$slug] ?? [];
    }

    /** Saves the whole of a person's page: text, lists and layout. */
    public static function saveAll(string $slug, array $payload): void
    {
        $all = static::all();
        $entry = $all[$slug] ?? [];

        if (array_key_exists('fields', $payload)) {
            $clean = [];
            foreach (static::FIELDS as $field) {
                foreach (['en', 'bn'] as $lang) {
                    $v = trim($payload['fields'][$field][$lang] ?? '');
                    if ($v !== '') {
                        $clean[$field][$lang] = $v;
                    }
                }
            }
            $entry['fields'] = $clean;
        }

        if (array_key_exists('lists', $payload)) {
            $entry['lists'] = static::cleanLists($payload['lists']);
        }

        if (array_key_exists('sections', $payload)) {
            $entry['sections'] = array_values(array_map(
                fn ($s) => ['key' => (string) $s['key'], 'on' => (bool) ($s['on'] ?? false)],
                array_filter($payload['sections'], fn ($s) => is_array($s) && filled($s['key'] ?? null))
            ));
        }

        $all[$slug] = $entry;

        File::ensureDirectoryExists(dirname(static::path()));
        File::put(static::path(), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Drops rows the person left entirely blank.
     *
     * An empty row is somebody who clicked "add" and changed their mind, not a
     * blank entry they want on the page. Rows with something in them are kept
     * exactly as given, including a half-filled one — the missing language is
     * their business, not ours to guess.
     */
    private static function cleanLists(array $lists): array
    {
        $out = [];

        foreach (['roles', 'education', 'languages', 'focus'] as $name) {
            $rows = $lists[$name] ?? null;
            if (! is_array($rows)) {
                continue;
            }

            $kept = [];
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $row = static::trimDeep($row);
                if (static::isBlank($row)) {
                    continue;
                }

                if ($name === 'languages') {
                    $row['v'] = max(0, min(100, (int) ($row['v'] ?? 0)));
                }

                $kept[] = $row;
            }

            $out[$name] = array_values($kept);
        }

        return $out;
    }

    private static function trimDeep(array $row): array
    {
        foreach ($row as $k => $v) {
            $row[$k] = is_array($v) ? static::trimDeep($v) : (is_string($v) ? trim($v) : $v);
        }

        return $row;
    }

    /** Blank means every text in it is empty — a level of 0 does not count. */
    private static function isBlank(array $row): bool
    {
        foreach ($row as $k => $v) {
            if ($k === 'v') {
                continue;
            }
            if (is_array($v) ? ! static::isBlank($v) : $v !== '' && $v !== null) {
                return false;
            }
        }

        return true;
    }


    /**
     * What a page says out of the box — exported from the portfolios project
     * at build time so this never drifts from what the page actually renders.
     */
    public static function defaultsFor(string $slug): array
    {
        $file = base_path('portfolios/src/defaults.json');

        if (! File::exists($file)) {
            return ['fields' => [], 'lists' => []];
        }

        $all = json_decode(File::get($file), true) ?: [];

        return $all[$slug] ?? ['fields' => [], 'lists' => []];
    }

    /**
     * Everything the owner's editor needs to open filled in: their saved edits
     * where they have made any, and what the built page says everywhere else.
     *
     * Sections come back in the order they will appear, each with whether it
     * is shown. A section the page has gained since the person last saved is
     * appended and on, so a rebuild never silently hides new work.
     */
    public static function formFor(string $slug): array
    {
        $saved = static::for($slug);
        $defaults = static::defaultsFor($slug);

        $fields = [];
        $savedFields = $saved['fields'] ?? $saved;
        foreach (static::FIELDS as $field) {
            foreach (['en', 'bn'] as $lang) {
                $fields[$field][$lang] = $savedFields[$field][$lang] ?? ($defaults['fields'][$field][$lang] ?? '');
            }
        }

        $lists = [];
        foreach (static::LISTS as $name) {
            $lists[$name] = array_values($saved['lists'][$name] ?? $defaults['lists'][$name] ?? []);
        }

        $available = static::sectionsFor($slug);
        $chosen = collect($saved['sections'] ?? [])
            ->filter(fn ($s) => is_array($s) && filled($s['key'] ?? null))
            ->keyBy('key');

        $sections = $chosen
            ->map(fn ($s, $key) => [
                'key' => $key,
                'label' => collect($available)->firstWhere('key', $key)['label'] ?? $key,
                'on' => (bool) ($s['on'] ?? true),
            ])
            // A saved key for a section the page no longer has is dropped: it
            // would be a row nothing on the page answers to.
            ->filter(fn ($s) => collect($available)->contains('key', $s['key']));

        $added = collect($available)
            ->reject(fn ($s) => $chosen->has($s['key']))
            ->map(fn ($s) => ['key' => $s['key'], 'label' => $s['label'], 'on' => true]);

        return [
            'fields' => $fields,
            'lists' => $lists,
            'sections' => $sections->concat($added)->values()->all(),
        ];
    }

    /** Where a portfolio's built page lives, if it is on this machine. */
    public static function pageDir(string $slug): string
    {
        return base_path("portfolios/dist/{$slug}");
    }

    /**
     * Writes each person's overrides into their own page folder.
     *
     * Returns what happened per slug, so the admin sees which pages actually
     * picked the change up rather than a blanket "saved".
     */
    public static function publish(array $slugs): array
    {
        $all = static::all();
        $result = [];

        foreach ($slugs as $slug) {
            $dir = static::pageDir($slug);

            if (! File::isDirectory($dir)) {
                $result[$slug] = 'no page folder on this server';
                continue;
            }

            $written = File::put(
                $dir.'/data.json',
                json_encode($all[$slug] ?? new \stdClass, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            $result[$slug] = $written === false ? 'could not write' : 'published';
        }

        return $result;
    }
}
