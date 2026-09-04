<?php

namespace App\Http\Controllers;

/**
 * The eight family portfolios, reachable from the admin area.
 *
 * Each one is a static page built separately and served from its own
 * subdomain, so there is nothing here to edit — this is the index that says
 * they exist, who they belong to, and where to find them. Without it they are
 * eight URLs somebody has to remember.
 */
class PortfolioController extends Controller
{
    /**
     * Kept here rather than in the database because that is where they
     * actually live: the portfolios are built from portfolios/src/data.js and
     * deployed as files. A table would be a second copy free to disagree with
     * the pages themselves.
     */
    public const PORTFOLIOS = [
        ['slug' => 'ansary',   'name' => 'Mohammed Abdur Rahman Ansary',  'called' => 'Robin', 'role' => 'Chairman',  'work' => 'Businessman',              'icon' => '☕'],
        ['slug' => 'ashik',    'name' => 'Mohammed Ashikur Rahman',       'called' => 'Riyad', 'role' => 'Director',  'work' => 'Advocate & Philanthropist', 'icon' => '⚖️'],
        ['slug' => 'morsheda', 'name' => 'Mosammat Morsheda Khatun',      'called' => null,    'role' => 'Director',  'work' => 'Dentist',                  'icon' => '🦷'],
        ['slug' => 'atik',     'name' => 'Mohammed Atikur Rahman',        'called' => null,    'role' => 'Director',  'work' => 'Full-Stack Developer',     'icon' => '⌨️'],
        ['slug' => 'maria',    'name' => 'Mosammat Maria Khatun',         'called' => null,    'role' => 'Director',  'work' => 'Architect',                'icon' => '📐'],
        ['slug' => 'maimuna',  'name' => 'Mosammat Maimuna Khatun',       'called' => null,    'role' => 'Director',  'work' => 'Researcher · Economics',   'icon' => '📊'],
        ['slug' => 'anas',     'name' => 'Mohammed Azizur Rahman Anas',   'called' => null,    'role' => 'Director',  'work' => 'Pilot',                    'icon' => '✈️'],
        ['slug' => 'arafat',   'name' => 'Mohammed Ahmadur Rahman Arafat','called' => null,    'role' => 'Director',  'work' => 'Homesteader',              'icon' => '🌱'],
    ];

    public function index()
    {
        $portfolios = collect(self::PORTFOLIOS)->map(function (array $p) {
            $p['url'] = 'https://'.$p['slug'].'.khandanilegacy.com/';

            return $p;
        });

        return view('admin.portfolios', ['portfolios' => $portfolios]);
    }
}
