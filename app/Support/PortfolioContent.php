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
        $out = [];

        foreach (static::FIELDS as $field) {
            foreach (['en', 'bn'] as $lang) {
                $out[$field][$lang] = $saved[$field][$lang] ?? ($defaults[$field][$lang] ?? '');
            }
        }

        return $out;
    }

    public static function save(string $slug, array $values): void
    {
        $all = static::all();

        // Blank fields are dropped rather than stored: an empty override would
        // wipe the line the page already has, which is never what leaving a
        // box empty is meant to mean.
        $clean = [];
        foreach (static::FIELDS as $field) {
            foreach (['en', 'bn'] as $lang) {
                $v = trim($values[$field][$lang] ?? '');
                if ($v !== '') {
                    $clean[$field][$lang] = $v;
                }
            }
        }

        $all[$slug] = $clean;

        File::ensureDirectoryExists(dirname(static::path()));
        File::put(static::path(), json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
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
