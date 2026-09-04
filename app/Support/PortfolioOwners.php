<?php

namespace App\Support;

use App\Http\Controllers\PortfolioController;
use App\Models\Person;
use App\Models\User;

/**
 * Which person in the family tree each portfolio belongs to.
 *
 * Matched on the distinctive part of a name rather than the whole string,
 * because the tree holds the spelling somebody typed and the portfolio holds
 * the one they gave for the page — "Ashikur Rahman" and "Ashikur Rhaman" are
 * the same man. The match is resolved once and shown in the admin list, so a
 * wrong one is visible rather than silent.
 */
class PortfolioOwners
{
    /**
     * Enough of each name to be unambiguous within one family, and no more.
     * A first name alone would catch a cousin.
     */
    private const NEEDLES = [
        'ansary' => ['Abdur Rahman Ansary', 'Ansary'],
        'ashik' => ['Ashikur', 'Ashiqur'],
        'morsheda' => ['Morsheda'],
        'atik' => ['Atikur'],
        'maria' => ['Maria Khatun', 'Maria'],
        'maimuna' => ['Maimuna'],
        'anas' => ['Azizur Rahman Anas', 'Anas'],
        'arafat' => ['Ahmadur Rahman Arafat', 'Arafat'],
    ];

    /** Resolved once per request: the dashboard asks this on every load. */
    private static ?array $map = null;

    /**
     * slug => Person id, for every portfolio whose person is in the tree.
     *
     * One query for the lot. Names are compared in PHP rather than with eight
     * LIKE queries, and the needles are tried in the order they are written so
     * the fuller spelling wins where a person's record has it.
     */
    public static function map(): array
    {
        if (self::$map !== null) {
            return self::$map;
        }

        $people = Person::query()->get(['id', 'full_name']);
        $map = [];

        foreach (self::NEEDLES as $slug => $needles) {
            foreach ($needles as $needle) {
                $match = $people->first(fn (Person $p) => stripos($p->full_name, $needle) !== false);
                if ($match) {
                    $map[$slug] = $match->id;
                    break;
                }
            }
        }

        return self::$map = $map;
    }

    /** The Person a portfolio belongs to, or null if nobody matches. */
    public static function personFor(string $slug): ?Person
    {
        $id = self::map()[$slug] ?? null;

        return $id ? Person::find($id) : null;
    }

    /**
     * The portfolio this login owns, if any.
     *
     * Keyed off their Person rather than their name, so somebody who has not
     * claimed an account owns nothing — there is no login to edit from — and
     * so a second person of the same name does not inherit the page.
     */
    public static function slugFor(?User $user): ?string
    {
        $personId = $user?->person?->id;

        if (! $personId) {
            return null;
        }

        $slug = array_search($personId, self::map(), true);

        return $slug === false ? null : $slug;
    }

    public static function owns(?User $user, string $slug): bool
    {
        return $user !== null && self::slugFor($user) === $slug;
    }

    /** Forgets the resolved map — for tests, which build a tree per case. */
    public static function flush(): void
    {
        self::$map = null;
    }

    /** The portfolio's defaults, as the built page has them. */
    public static function defaults(string $slug): ?array
    {
        return collect(PortfolioController::PORTFOLIOS)->firstWhere('slug', $slug);
    }
}
