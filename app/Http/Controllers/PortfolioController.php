<?php

namespace App\Http\Controllers;

use App\Support\PortfolioContent;
use Illuminate\Http\Request;

/**
 * The eight family portfolios, listed and edited from the admin area.
 *
 * The pages themselves are static files on their own subdomains. What can be
 * changed here is their wording — saved to a JSON file, then published into
 * each page's folder for the page to read when it loads. Anything structural
 * (a new section, a different layout) is still a rebuild.
 */
class PortfolioController extends Controller
{
    /**
     * What each page says out of the box.
     *
     * These mirror portfolios/src/data.js so the edit form opens filled in
     * rather than blank, and so a page that has never been edited still shows
     * something sensible here.
     */
    public const PORTFOLIOS = [
        [
            'slug' => 'ansary', 'icon' => '☕',
            'name' => ['en' => 'Mohammed Abdur Rahman Ansary', 'bn' => 'মোহাম্মদ আব্দুর রহমান আনসারী'],
            'called' => ['en' => 'Robin', 'bn' => 'রবিন'],
            'role' => ['en' => 'Chairman · Khandani Legacy', 'bn' => 'চেয়ারম্যান · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Businessman', 'bn' => 'ব্যবসায়ী'],
            'tagline' => ['en' => 'Founder of Al-Wawah Cafe. Director at Provati Insurance Company Limited and 8 Bit Private Limited.', 'bn' => 'আল-ওয়াওয়াহ ক্যাফের প্রতিষ্ঠাতা। প্রভাতী ইন্স্যুরেন্স কোম্পানি লিমিটেড ও ৮ বিট প্রাইভেট লিমিটেডের পরিচালক।'],
            'speech' => ['en' => 'A cafe is not sold in cups. It is sold in the hour someone chooses to spend with you instead of anywhere else.', 'bn' => 'ক্যাফে কাপে বিক্রি হয় না। বিক্রি হয় সেই ঘণ্টাটিতে, যেটি কেউ অন্য কোথাও না গিয়ে আপনার সঙ্গে কাটাতে বেছে নেয়।'],
        ],
        [
            'slug' => 'ashik', 'icon' => '⚖️',
            'name' => ['en' => 'Mohammed Ashikur Rahman', 'bn' => 'মোহাম্মদ আশিকুর রহমান'],
            'called' => ['en' => 'Riyad', 'bn' => 'রিয়াদ'],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Advocate & Philanthropist', 'bn' => 'আইনজীবী ও সমাজসেবী'],
            'tagline' => ['en' => 'Managing Director at 8 Bit Private Limited, proprietor of Octate Construction & Supplies, founder of Ayesha Foundation.', 'bn' => '৮ বিট প্রাইভেট লিমিটেডের ব্যবস্থাপনা পরিচালক, অক্টেট কনস্ট্রাকশন অ্যান্ড সাপ্লাইজের স্বত্বাধিকারী, আয়েশা ফাউন্ডেশনের প্রতিষ্ঠাতা।'],
            'speech' => ['en' => 'Representing a child who has no one is not a case on a list. True success lies in giving back to the community.', 'bn' => 'যে শিশুর কেউ নেই, তার পক্ষে দাঁড়ানো কেবল তালিকার একটি মামলা নয়। প্রকৃত সাফল্য সমাজকে ফিরিয়ে দেওয়ার মধ্যে।'],
        ],
        [
            'slug' => 'morsheda', 'icon' => '🦷',
            'name' => ['en' => 'Mosammat Morsheda Khatun', 'bn' => 'মোসাম্মৎ মোরশেদা খাতুন'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Dentist', 'bn' => 'দন্তচিকিৎসক'],
            'tagline' => ['en' => 'Trained at Pioneer Dental College and North South University, now reading for a PhD at Hokkaido University in Japan.', 'bn' => 'পাইওনিয়ার ডেন্টাল কলেজ ও নর্থ সাউথ ইউনিভার্সিটিতে প্রশিক্ষিত, বর্তমানে জাপানের হোক্কাইদো বিশ্ববিদ্যালয়ে পিএইচডি করছেন।'],
            'speech' => ['en' => 'Most people arrive at a dentist already braced for it. Half of what I do is undo that before I have picked anything up.', 'bn' => 'বেশির ভাগ মানুষ দন্তচিকিৎসকের কাছে আসেন আগে থেকেই ভয় নিয়ে। আমার কাজের অর্ধেকটাই সেই ভয় দূর করা।'],
        ],
        [
            'slug' => 'atik', 'icon' => '⌨️',
            'name' => ['en' => 'Mohammed Atikur Rahman', 'bn' => 'মোহাম্মদ আতিকুর রহমান'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Full-Stack Developer & DevOps', 'bn' => 'ফুল-স্ট্যাক ডেভেলপার ও ডেভঅপস'],
            'tagline' => ['en' => 'CEO of 8 Bit Private Limited. Backend first, from the database up to the pixel.', 'bn' => '৮ বিট প্রাইভেট লিমিটেডের সিইও। ব্যাকএন্ড আগে, ডেটাবেজ থেকে পিক্সেল পর্যন্ত।'],
            'speech' => ['en' => 'Code that works is the low bar. Code someone can still read a year from now is the actual job.', 'bn' => 'কোড কাজ করলেই হলো — এটা সবচেয়ে সহজ শর্ত। এক বছর পরেও যেন কেউ পড়ে বুঝতে পারে, সেটাই আসল কাজ।'],
        ],
        [
            'slug' => 'maria', 'icon' => '📐',
            'name' => ['en' => 'Mosammat Maria Khatun', 'bn' => 'মোসাম্মৎ মারিয়া খাতুন'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Architect', 'bn' => 'স্থপতি'],
            'tagline' => ['en' => 'Architect, trained at North South University. Founder of Bikku Bikku.', 'bn' => 'স্থপতি, নর্থ সাউথ ইউনিভার্সিটিতে প্রশিক্ষিত। বিক্কু বিক্কুর প্রতিষ্ঠাতা।'],
            'speech' => ['en' => 'A building is a promise you make to people you will never meet. I like that it has to be kept in millimetres.', 'bn' => 'একটি ভবন হলো এমন মানুষদের দেওয়া প্রতিশ্রুতি, যাদের সঙ্গে কোনোদিন দেখা হবে না। ভালো লাগে যে তা মিলিমিটারে রক্ষা করতে হয়।'],
        ],
        [
            'slug' => 'maimuna', 'icon' => '📊',
            'name' => ['en' => 'Mosammat Maimuna Khatun', 'bn' => 'মোসাম্মৎ মাইমুনা খাতুন'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Researcher · Economics', 'bn' => 'গবেষক · অর্থনীতি'],
            'tagline' => ['en' => "Bachelor in Economics and two Master's degrees from Independent University, Bangladesh. Co-founder of Bikku Bikku.", 'bn' => 'ইন্ডিপেনডেন্ট ইউনিভার্সিটি, বাংলাদেশ থেকে অর্থনীতিতে স্নাতক ও দুটি স্নাতকোত্তর। বিক্কু বিক্কুর সহ-প্রতিষ্ঠাতা।'],
            'speech' => ['en' => 'Everyone already knows what they think before the data arrives. The discipline is letting the numbers say no to you.', 'bn' => 'উপাত্ত আসার আগেই সবাই জানে তারা কী ভাবে। গোটা বিদ্যাটাই সংখ্যাকে না বলতে দেওয়ার অভ্যাস।'],
        ],
        [
            'slug' => 'anas', 'icon' => '✈️',
            'name' => ['en' => 'Mohammed Azizur Rahman Anas', 'bn' => 'মোহাম্মদ আজিজুর রহমান আনাস'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Pilot', 'bn' => 'পাইলট'],
            'tagline' => ['en' => 'Pilot, trained at the Malaysian Flying Academy. Marketing Officer at Al-Wawah.', 'bn' => 'পাইলট, মালয়েশিয়ান ফ্লাইং একাডেমিতে প্রশিক্ষিত। আল-ওয়াওয়াহর মার্কেটিং অফিসার।'],
            'speech' => ['en' => 'Flying is the same checklist, read the same way, on the good days and the bad ones. The discipline is the romance.', 'bn' => 'ওড়া মানে একই চেকলিস্ট, একইভাবে পড়া — ভালো দিনেও, খারাপ দিনেও। নিয়মানুবর্তিতাই এখানে রোমান্স।'],
        ],
        [
            'slug' => 'arafat', 'icon' => '🌱',
            'name' => ['en' => 'Mohammed Ahmadur Rahman Arafat', 'bn' => 'মোহাম্মদ আহমাদুর রহমান আরাফাত'],
            'called' => ['en' => '', 'bn' => ''],
            'role' => ['en' => 'Director · Khandani Legacy', 'bn' => 'পরিচালক · খানদানি লিগ্যাসি'],
            'profession' => ['en' => 'Homesteader', 'bn' => 'হোমস্টেডার'],
            'tagline' => ['en' => 'Co-founder of Bikku Bikku. Works at the pace things actually grow at.', 'bn' => 'বিক্কু বিক্কুর সহ-প্রতিষ্ঠাতা। কাজ করেন সেই গতিতে, যে গতিতে জিনিস সত্যিই বাড়ে।'],
            'speech' => ['en' => 'Nothing on a homestead can be hurried. You do the work in the right week or you do without for the year.', 'bn' => 'খামারে কোনো কিছুই তাড়াহুড়ো করা যায় না। ঠিক সপ্তাহে কাজটি করবেন, নয়তো সারা বছর তা ছাড়াই কাটাবেন।'],
        ],
    ];

    private static function find(string $slug): array
    {
        $found = collect(self::PORTFOLIOS)->firstWhere('slug', $slug);
        abort_unless($found, 404);

        return $found;
    }

    public function index()
    {
        $saved = PortfolioContent::all();

        $portfolios = collect(self::PORTFOLIOS)->map(function (array $p) use ($saved) {
            $p['url'] = 'https://'.$p['slug'].'.khandanilegacy.com/';
            $p['edited'] = ! empty($saved[$p['slug']]);
            $p['published'] = file_exists(PortfolioContent::pageDir($p['slug']).'/data.json');

            return $p;
        });

        return view('admin.portfolios', ['portfolios' => $portfolios]);
    }

    public function edit(string $slug)
    {
        $portfolio = self::find($slug);

        return view('admin.portfolio-edit', [
            'portfolio' => $portfolio,
            'values' => PortfolioContent::withDefaults($slug, $portfolio),
        ]);
    }

    public function update(Request $request, string $slug)
    {
        self::find($slug);

        $rules = [];
        foreach (PortfolioContent::FIELDS as $f) {
            // Generous limits: a speech is a paragraph, a name is not.
            $max = in_array($f, ['tagline', 'speech'], true) ? 1200 : 160;
            $rules["fields.$f.en"] = ['nullable', 'string', "max:$max"];
            $rules["fields.$f.bn"] = ['nullable', 'string', "max:$max"];
        }

        $validated = $request->validate($rules);
        PortfolioContent::save($slug, $validated['fields'] ?? []);

        return redirect()
            ->route('admin.portfolios.index')
            ->with('status', 'portfolio-saved')
            ->with('saved_slug', $slug);
    }

    /**
     * Lays the saved wording beside each page as data.json.
     *
     * Separate from saving so that editing several people is one publish, and
     * so a half-finished edit is not on the live page the moment it is typed.
     */
    public function publish()
    {
        $result = PortfolioContent::publish(collect(self::PORTFOLIOS)->pluck('slug')->all());

        return redirect()
            ->route('admin.portfolios.index')
            ->with('status', 'portfolios-published')
            ->with('publish_result', $result);
    }
}
