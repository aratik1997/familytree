/**
 * Everything the eight pages say, in both languages.
 *
 * Kept as data rather than JSX so a correction — a degree, a language score,
 * a line of the speech — is a one-line edit somebody non-technical can find,
 * rather than a hunt through a component.
 *
 * Language scores are one number per language, deliberately: a single "how
 * much of this language do you have" rather than separate marks for speaking
 * and writing.
 */

const t = (en, bn) => ({ en, bn });

export const PEOPLE = {
  ansary: {
    slug: 'ansary',
    called: t('Robin', 'রবিন'),
    short: t('Ansary', 'আনসারী'),
    name: t('Mohammed Abdur Rahman Ansary', 'মোহাম্মদ আব্দুর রহমান আনসারী'),
    role: t('Chairman · Khandani Legacy', 'চেয়ারম্যান · খানদানি লিগ্যাসি'),
    profession: t('Businessman', 'ব্যবসায়ী'),
    tagline: t(
      'Founder of Al-Wawah Cafe. Director at Provati Insurance Company Limited and 8 Bit Private Limited. A businessman who builds places people want to sit down in.',
      'আল-ওয়াওয়াহ ক্যাফের প্রতিষ্ঠাতা। প্রভাতী ইন্স্যুরেন্স কোম্পানি লিমিটেড ও ৮ বিট প্রাইভেট লিমিটেডের পরিচালক। একজন ব্যবসায়ী, যিনি এমন জায়গা গড়েন যেখানে মানুষ বসতে চায়।'
    ),
    speech: t(
      'A cafe is not sold in cups. It is sold in the hour someone chooses to spend with you instead of anywhere else. Build the hour properly and the cup takes care of itself — and that is true of every business I have ever put my name to.',
      'ক্যাফে কাপে বিক্রি হয় না। বিক্রি হয় সেই ঘণ্টাটিতে, যেটি কেউ অন্য কোথাও না গিয়ে আপনার সঙ্গে কাটাতে বেছে নেয়। ঘণ্টাটি ঠিকভাবে গড়ুন, কাপ নিজের যত্ন নিজেই নেবে — আর আমি যত ব্যবসায় নাম দিয়েছি, সবগুলোর ক্ষেত্রেই কথাটি সত্যি।'
    ),
    roles: [
      { icon: '🏛️', title: t('Chairman', 'চেয়ারম্যান'), org: 'Khandani Legacy',
        note: t("Head of the family's collective business interests.", 'পরিবারের সম্মিলিত ব্যবসায়িক স্বার্থের প্রধান।') },
      { icon: '☕', title: t('Owner & Founder', 'স্বত্বাধিকারী ও প্রতিষ্ঠাতা'), org: 'Al-Wawah Cafe · Uttara, Dhaka',
        note: t('Cafe, bistro and bakery — premium coffee, fresh bakes, a room worth staying in.', 'ক্যাফে, বিস্ট্রো ও বেকারি — প্রিমিয়াম কফি, তাজা বেকিং, বসে থাকার মতো এক পরিবেশ।') },
      { icon: '🛡️', title: t('Director', 'পরিচালক'), org: 'Provati Insurance Company Limited',
        note: t("On the board of one of the country's insurance houses.", 'দেশের অন্যতম বিমা প্রতিষ্ঠানের পরিচালনা পর্ষদে।') },
      { icon: '💻', title: t('Director', 'পরিচালক'), org: '8 Bit Private Limited',
        note: t("The technology arm of the family's holdings.", 'পরিবারের প্রতিষ্ঠানসমূহের প্রযুক্তি শাখা।') },
    ],
    education: [
      { school: 'Sarsina Madrasa', where: t('Barisal', 'বরিশাল') },
      { school: 'Darun Najat Siddikia Kamil Madrasa', where: t('Demra, Dhaka', 'ডেমরা, ঢাকা') },
      { school: 'Daulatganj Gajimura Kamil Madrasa', where: t('Laksam, Cumilla', 'লাকসাম, কুমিল্লা') },
      { school: 'Green University of Bangladesh', where: t('Dhaka', 'ঢাকা') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('Farsi', 'ফারসি'), level: t('Conversational', 'কথোপকথন'), v: 55 },
      { name: t('Hindi', 'হিন্দি'), level: t('Understands', 'বোঝেন'), v: 45 },
      { name: t('Arabic', 'আরবি'), level: t('Basic', 'প্রাথমিক'), v: 35 },
    ],
    focus: [
      t('Hospitality', 'আতিথেয়তা'), t('Insurance', 'বিমা'), t('Technology', 'প্রযুক্তি'),
      t('Investment', 'বিনিয়োগ'), t('Brand building', 'ব্র্যান্ড গঠন'), t('Team building', 'দল গঠন'),
    ],
    venue: {
      name: 'Al-Wawah',
      kind: t('Cafe · Bistro · Bakery', 'ক্যাফে · বিস্ট্রো · বেকারি'),
      where: t('Canyon Tower, Sonargaon Janapath Road, Uttara, Dhaka 1230', 'ক্যানিয়ন টাওয়ার, সোনারগাঁও জনপথ রোড, উত্তরা, ঢাকা ১২৩০'),
      line: t('A new experience awaits you — great food, rich flavours and unforgettable moments.', 'এক নতুন অভিজ্ঞতা অপেক্ষা করছে — দারুণ খাবার, গভীর স্বাদ আর অবিস্মরণীয় মুহূর্ত।'),
      menu: [
        t('Espresso and specialty coffee', 'এসপ্রেসো ও স্পেশালটি কফি'),
        t('Freshly baked breads and pastries', 'তাজা বেক করা রুটি ও পেস্ট্রি'),
        t('Pasta, sliders and all-day breakfast', 'পাস্তা, স্লাইডার ও সারাদিনের ব্রেকফাস্ট'),
        t('Fresh juices', 'তাজা জুস'),
      ],
    },
    links: [{ label: 'alwawah.com', href: 'https://alwawah.com' }],
  },

  ashik: {
    slug: 'ashik',
    name: t('Mohammed Ashikur Rahman', 'মোহাম্মদ আশিকুর রহমান'),
    called: t('Riyad', 'রিয়াদ'),
    short: t('M. A. Rahman', 'এম. এ. রহমান'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Advocate & Philanthropist', 'আইনজীবী ও সমাজসেবী'),
    tagline: t(
      'Managing Director at 8 Bit Private Limited, proprietor of Octate Construction & Supplies, founder of Ayesha Foundation. Uses a legal training not for profit but for purpose — child rights and juvenile justice.',
      '৮ বিট প্রাইভেট লিমিটেডের ব্যবস্থাপনা পরিচালক, অক্টেট কনস্ট্রাকশন অ্যান্ড সাপ্লাইজের স্বত্বাধিকারী, আয়েশা ফাউন্ডেশনের প্রতিষ্ঠাতা। আইনের শিক্ষা মুনাফার জন্য নয়, উদ্দেশ্যের জন্য — শিশু অধিকার ও কিশোর ন্যায়বিচার।'
    ),
    speech: t(
      'Representing a child who has no one is not a case on a list. It is the difference between a life that gets a second chance and one that does not. True success lies in giving back to the community and fulfilling our shared human responsibilities.',
      'যে শিশুর কেউ নেই, তার পক্ষে দাঁড়ানো কেবল তালিকার একটি মামলা নয়। এটি এমন এক জীবনের পার্থক্য গড়ে দেয়, যে দ্বিতীয় সুযোগ পায় আর যে পায় না। প্রকৃত সাফল্য সমাজকে ফিরিয়ে দেওয়ার মধ্যে, আমাদের অভিন্ন মানবিক দায়িত্ব পালনের মধ্যে।'
    ),
    roles: [
      { icon: '⚖️', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
      { icon: '💻', title: t('Managing Director', 'ব্যবস্থাপনা পরিচালক'), org: '8 Bit Private Limited' },
      { icon: '🏗️', title: t('Proprietor', 'স্বত্বাধিকারী'), org: 'Octate Construction & Supplies' },
      { icon: '🌱', title: t('Founder & Chairman', 'প্রতিষ্ঠাতা ও চেয়ারম্যান'), org: 'Ayesha Foundation',
        note: t('Non-profit for community welfare across Bangladesh.', 'বাংলাদেশজুড়ে সমাজকল্যাণে নিবেদিত অলাভজনক সংস্থা।') },
    ],
    practice: [
      { title: t('Juvenile Justice', 'কিশোর ন্যায়বিচার'),
        note: t('Advocacy for child rights, so vulnerable children get fair representation.', 'শিশু অধিকারের পক্ষে ওকালতি — যেন অসহায় শিশুরা ন্যায্য প্রতিনিধিত্ব পায়।') },
      { title: t('Legal Documents', 'আইনি দলিল'),
        note: t('Drafting, review and diligence — the paperwork a business stands or falls on.', 'খসড়া, পর্যালোচনা ও যাচাই — যে কাগজপত্রের উপর ব্যবসা দাঁড়ায় বা পড়ে।') },
      { title: t('Charity Work', 'দাতব্য কাজ'),
        note: t('A family legacy of welfare in Laksam, Cumilla spanning generations.', 'লাকসাম, কুমিল্লায় প্রজন্মের পর প্রজন্ম ধরে চলা পারিবারিক কল্যাণকর্মের ধারা।') },
    ],
    education: [
      { school: 'Abdul Malek Institution', where: t('Laksam', 'লাকসাম') },
      { school: 'Cumilla Cadet College', where: t('Cumilla', 'কুমিল্লা') },
      { school: 'London College of Legal Studies', where: t('LLB', 'এলএলবি') },
      { school: 'International Islamic University Malaysia', where: t('Business Administration', 'ব্যবসায় প্রশাসন') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('Hindi', 'হিন্দি'), level: t('Understands', 'বোঝেন'), v: 45 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 40 },
    ],
    focus: [
      t('Accounting', 'হিসাবরক্ষণ'), t('Market analysis', 'বাজার বিশ্লেষণ'), t('Market research', 'বাজার গবেষণা'),
      t('Investment management', 'বিনিয়োগ ব্যবস্থাপনা'), t('Export and import', 'আমদানি-রপ্তানি'),
      t('Economic analysis', 'অর্থনৈতিক বিশ্লেষণ'), t('Statistics', 'পরিসংখ্যান'), t('Juvenile law', 'কিশোর আইন'),
    ],
    companies: [
      { role: t('Managing Director', 'ব্যবস্থাপনা পরিচালক'), org: 'Provati Courier Limited', sector: t('Courier and logistics', 'কুরিয়ার ও লজিস্টিকস') },
      { role: t('Managing Director', 'ব্যবস্থাপনা পরিচালক'), org: 'M.R. Traders', sector: t('Import and export', 'আমদানি-রপ্তানি') },
      { role: t('Chairman', 'চেয়ারম্যান'), org: 'Bhaiya Agro, Dairy and Fisheries', sector: t('Agriculture and food', 'কৃষি ও খাদ্য') },
      { role: t('Director', 'পরিচালক'), org: 'Bhaiya Group of Industries', sector: t('Industrial conglomerate', 'শিল্প গোষ্ঠী') },
      { role: t('Director', 'পরিচালক'), org: 'Sattar Match Works', sector: t('Manufacturing', 'উৎপাদন') },
      { role: t('Director', 'পরিচালক'), org: 'Hac Securities Limited', sector: t('Securities and finance', 'সিকিউরিটিজ ও অর্থ') },
      { role: t('Proprietor', 'স্বত্বাধিকারী'), org: 'Khandani Traders', sector: t('Trading', 'বাণিজ্য') },
      { role: t('Proprietor', 'স্বত্বাধিকারী'), org: 'Ayesha Enterprise', sector: t('Enterprise', 'এন্টারপ্রাইজ') },
    ],
    memberships: [
      { icon: '🎖️', name: 'Cadet College Club', note: t('Member', 'সদস্য') },
      { icon: '🌐', name: 'Junior Chamber International', note: t('Member', 'সদস্য') },
      { icon: '🌱', name: 'Ayesha Foundation', note: t('Founding board member', 'প্রতিষ্ঠাতা বোর্ড সদস্য') },
    ],
    home: t('Laksam, Cumilla, Bangladesh', 'লাকসাম, কুমিল্লা, বাংলাদেশ'),
    links: [],
  },

  morsheda: {
    slug: 'morsheda',
    short: t('Dr. Morsheda', 'ডা. মোরশেদা'),
    name: t('Mosammat Morsheda Khatun', 'মোসাম্মৎ মোরশেদা খাতুন'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Dentist', 'দন্তচিকিৎসক'),
    tagline: t(
      'Trained at Pioneer Dental College and North South University, now reading for a PhD at Hokkaido University in Japan.',
      'পাইওনিয়ার ডেন্টাল কলেজ ও নর্থ সাউথ ইউনিভার্সিটিতে প্রশিক্ষিত, বর্তমানে জাপানের হোক্কাইদো বিশ্ববিদ্যালয়ে পিএইচডি করছেন।'
    ),
    speech: t(
      'Most people arrive at a dentist already braced for it. Half of what I do is undo that before I have picked anything up. A calm patient heals better, comes back sooner, and tells the truth about what hurts — and none of that is written in a textbook.',
      'বেশির ভাগ মানুষ দন্তচিকিৎসকের কাছে আসেন আগে থেকেই ভয় নিয়ে। আমার কাজের অর্ধেকটাই সেই ভয় দূর করা, কিছু হাতে নেওয়ার আগেই। শান্ত রোগী দ্রুত সুস্থ হন, আবার আসেন, আর কোথায় ব্যথা তা সত্যি করে বলেন — এর কোনোটাই পাঠ্যবইয়ে লেখা নেই।'
    ),
    roles: [
      { icon: '🦷', title: t('Clinical Dentistry', 'ক্লিনিক্যাল ডেন্টিস্ট্রি'), org: t('Practice', 'চিকিৎসা'),
        note: t('Everyday care, done gently and explained plainly.', 'প্রতিদিনের চিকিৎসা — যত্ন করে, সহজ করে বুঝিয়ে।') },
      { icon: '🔬', title: t('Research', 'গবেষণা'), org: 'Hokkaido University',
        note: t('Doctoral work, where the questions get harder.', 'ডক্টরাল গবেষণা, যেখানে প্রশ্নগুলো আরও কঠিন।') },
      { icon: '💚', title: t('Patient Care', 'রোগীর যত্ন'), org: t('Every day', 'প্রতিদিন'),
        note: t('Taking the fear out of the chair, one appointment at a time.', 'চেয়ারের ভয় দূর করা, এক একটি অ্যাপয়েন্টমেন্টে।') },
    ],
    education: [
      { school: 'Pioneer Dental College', where: t('Dhaka · Dental surgery', 'ঢাকা · দন্তশল্য') },
      { school: 'North South University', where: t('Dhaka', 'ঢাকা') },
      { school: 'Hokkaido University', where: t('Japan · PhD', 'জাপান · পিএইচডি') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('Hindi', 'হিন্দি'), level: t('Understands', 'বোঝেন'), v: 45 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 40 },
      { name: t('Japanese', 'জাপানি'), level: t('Basic', 'প্রাথমিক'), v: 30 },
    ],
    focus: [
      t('Clinical dentistry', 'ক্লিনিক্যাল ডেন্টিস্ট্রি'), t('Oral health', 'মুখগহ্বরের স্বাস্থ্য'),
      t('Preventive care', 'প্রতিরোধমূলক সেবা'), t('Dental research', 'দন্ত গবেষণা'),
      t('Patient communication', 'রোগীর সঙ্গে যোগাযোগ'), t('Academic writing', 'একাডেমিক লেখা'),
    ],
    journey: [
      { n: '01', place: 'Pioneer Dental College', what: t('Dental surgery, Dhaka', 'দন্তশল্য, ঢাকা') },
      { n: '02', place: 'North South University', what: t('Further study, Dhaka', 'উচ্চতর পড়াশোনা, ঢাকা') },
      { n: '03', place: 'Hokkaido University', what: t('Doctoral research, Japan', 'ডক্টরাল গবেষণা, জাপান') },
    ],
    links: [],
  },

  atik: {
    slug: 'atik',
    short: t('Atikur Rahman', 'আতিকুর রহমান'),
    name: t('Mohammed Atikur Rahman', 'মোহাম্মদ আতিকুর রহমান'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Full-Stack Developer & DevOps', 'ফুল-স্ট্যাক ডেভেলপার ও ডেভঅপস'),
    tagline: t(
      'CEO of 8 Bit Private Limited. Backend first, from the database up to the pixel.',
      '৮ বিট প্রাইভেট লিমিটেডের সিইও। ব্যাকএন্ড আগে, ডেটাবেজ থেকে পিক্সেল পর্যন্ত।'
    ),
    speech: t(
      'Code that works is the low bar. Code someone can still read a year from now, when I have forgotten why I wrote it and the person fixing it has never met me — that is the actual job. Everything else is typing.',
      'কোড কাজ করলেই হলো — এটা সবচেয়ে সহজ শর্ত। এক বছর পরেও যেন কেউ পড়ে বুঝতে পারে, যখন আমি নিজেই ভুলে গেছি কেন লিখেছিলাম আর যে ঠিক করছে সে আমাকে কোনোদিন দেখেনি — সেটাই আসল কাজ। বাকিটা শুধু টাইপ করা।'
    ),
    roles: [
      { icon: '⌨️', title: 'CEO', org: '8 Bit Private Limited',
        note: t('Leads the technology company end to end.', 'প্রযুক্তি প্রতিষ্ঠানটির সম্পূর্ণ নেতৃত্ব দেন।') },
      { icon: '🗄️', title: t('Backend & DevOps', 'ব্যাকএন্ড ও ডেভঅপস'), org: t('Day to day', 'প্রতিদিন'),
        note: t('Databases, servers, security updates, keeping the lights on.', 'ডেটাবেজ, সার্ভার, নিরাপত্তা হালনাগাদ — আর সবকিছু সচল রাখা।') },
      { icon: '🌳', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
    ],
    skills: [
      { label: 'Backend Engineering', v: 90 },
      { label: 'REST API Development', v: 88 },
      { label: 'Database Design', v: 85 },
      { label: 'Frontend · React / Next.js', v: 82 },
      { label: 'System Design', v: 78 },
      { label: 'Mobile · Flutter / Dart', v: 75 },
      { label: 'Cloud & DevOps', v: 70 },
    ],
    stack: ['PHP', 'MySQL', 'JavaScript', 'Laravel', 'React', 'Flutter', 'Docker', 'Security'],
    education: [
      { school: 'Daulatganj Gazimura Kamil Madrasah', where: t('Dakhil · GPA 4.22', 'দাখিল · জিপিএ ৪.২২') },
      { school: 'Eminence College', where: t('HSC · GPA 3.92', 'এইচএসসি · জিপিএ ৩.৯২') },
      { school: 'North South University', where: t('BSc · Computer Science', 'বিএসসি · কম্পিউটার সায়েন্স') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 45 },
    ],
    focus: [
      t('Security updates', 'নিরাপত্তা হালনাগাদ'), t('Database manipulation', 'ডেটাবেজ ব্যবস্থাপনা'),
      t('Problem solving', 'সমস্যা সমাধান'), t('Backend coding', 'ব্যাকএন্ড কোডিং'),
      t('System architecture', 'সিস্টেম আর্কিটেকচার'), t('Excel expertise', 'এক্সেল দক্ষতা'),
    ],
    projects: [
      { name: 'poker', lang: 'PHP',
        note: t('Multiplayer Texas hold-em for 2 to 8 players — host controls, live chat, spectator mode and a full side-pot betting engine.', '২ থেকে ৮ জনের মাল্টিপ্লেয়ার টেক্সাস হোল্ড-এম — হোস্ট নিয়ন্ত্রণ, লাইভ চ্যাট, দর্শক মোড আর পূর্ণাঙ্গ সাইড-পট বেটিং ইঞ্জিন।'),
        url: 'https://github.com/aratik1997/poker' },
      { name: 'twentynine', lang: 'PHP',
        note: t('Real-time multiplayer 29 — the South Asian trick-taking game. Fixed partnerships, secret trump, bidding, live table with chat and sound.', 'রিয়েল-টাইম মাল্টিপ্লেয়ার ২৯ — দক্ষিণ এশিয়ার জনপ্রিয় তাসের খেলা। নির্দিষ্ট জুটি, গোপন তুরুপ, ডাক, লাইভ টেবিল, চ্যাট ও শব্দ।'),
        url: 'https://github.com/aratik1997/twentynine' },
    ],
    experience: {
      role: t('Backend Developer', 'ব্যাকএন্ড ডেভেলপার'), org: '8 Bit (Pvt) Limited', period: '2024 — present',
      bullets: [
        t('Update all of the company websites in an efficient, maintainable way.', 'কোম্পানির সব ওয়েবসাইট দক্ষ ও রক্ষণাবেক্ষণযোগ্য উপায়ে হালনাগাদ করা।'),
        t('Ship security updates that keep company data and information secure.', 'নিরাপত্তা হালনাগাদ প্রকাশ, যা কোম্পানির তথ্য নিরাপদ রাখে।'),
        t('Maintain the company database, servers and other information sources.', 'কোম্পানির ডেটাবেজ, সার্ভার ও অন্যান্য তথ্যভাণ্ডার রক্ষণাবেক্ষণ।'),
      ],
    },
    travel: ['🇧🇩', '🇮🇳', '🇲🇾', '🇸🇦', '🇦🇪', '🇸🇬', '🇹🇭'],
    hobbies: [t('Gaming', 'গেমিং'), t('Coding', 'কোডিং'), t('Traveling', 'ভ্রমণ'), t('Movies', 'সিনেমা')],
    home: t('Uttara, Dhaka, Bangladesh', 'উত্তরা, ঢাকা, বাংলাদেশ'),
    links: [{ label: 'github.com/aratik1997', href: 'https://github.com/aratik1997' }],
  },

  maria: {
    slug: 'maria',
    short: t('Maria', 'মারিয়া'),
    name: t('Mosammat Maria Khatun', 'মোসাম্মৎ মারিয়া খাতুন'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Architect', 'স্থপতি'),
    tagline: t(
      'Architect, trained at North South University. Founder of Bikku Bikku — cookies baked from scratch, which turns out to be another kind of drawing that has to hold together.',
      'স্থপতি, নর্থ সাউথ ইউনিভার্সিটিতে প্রশিক্ষিত। বিক্কু বিক্কুর প্রতিষ্ঠাতা — একেবারে শুরু থেকে বানানো কুকি, যা আসলে আরেক ধরনের নকশা, যাকে ঠিকঠাক দাঁড়াতে হয়।'
    ),
    speech: t(
      'A building is a promise you make to people you will never meet — that the stair will hold, that the light will arrive in the afternoon, that the room will make sense without a manual. I like that the promise has to be kept in millimetres.',
      'একটি ভবন হলো এমন মানুষদের দেওয়া প্রতিশ্রুতি, যাদের সঙ্গে আপনার কোনোদিন দেখা হবে না — সিঁড়ি ধরে রাখবে, বিকেলে আলো এসে পড়বে, ঘরটি কোনো নির্দেশিকা ছাড়াই বোঝা যাবে। আমার ভালো লাগে যে এই প্রতিশ্রুতি মিলিমিটারে রক্ষা করতে হয়।'
    ),
    roles: [
      { icon: '📐', title: t('Architecture', 'স্থাপত্য'), org: t('B.Arch · North South University', 'বি.আর্ক · নর্থ সাউথ ইউনিভার্সিটি'),
        note: t('Drawing, detailing, and the long argument between what is wanted and what will stand.', 'নকশা, খুঁটিনাটি, আর যা চাওয়া হয় ও যা দাঁড়াবে — এই দুইয়ের দীর্ঘ তর্ক।') },
      { icon: '🍪', title: t('Founder · Bikku Bikku', 'প্রতিষ্ঠাতা · বিক্কু বিক্কু'), org: t('Homemade cookies', 'ঘরে বানানো কুকি'),
        note: t('A small bakery brand — freshly baked, nothing bought in.', 'ছোট এক বেকারি ব্র্যান্ড — তাজা বেকিং, বাইরে থেকে কিছুই কেনা নয়।') },
      { icon: '🏛️', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
    ],
    education: [
      { school: 'Uttara High School & College', where: t('Dhaka', 'ঢাকা') },
      { school: 'North South University', where: t('Bachelor of Architecture', 'স্থাপত্যে স্নাতক') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('French', 'ফরাসি'), level: t('Intermediate', 'মাধ্যমিক'), v: 60 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 45 },
    ],
    focus: [
      t('Architectural design', 'স্থাপত্য নকশা'), t('Technical drawing', 'কারিগরি অঙ্কন'),
      t('Space planning', 'স্থান পরিকল্পনা'), t('Detailing', 'খুঁটিনাটি নকশা'),
      t('Material selection', 'উপকরণ নির্বাচন'), t('Recipe development', 'রেসিপি উন্নয়ন'),
    ],
    method: [
      { n: '01', title: t('Survey', 'জরিপ'), note: t('What is there, measured before anything is imagined.', 'কী আছে — কল্পনার আগেই মেপে নেওয়া।') },
      { n: '02', title: t('Sketch', 'স্কেচ'), note: t('The argument between what is wanted and what will stand.', 'যা চাওয়া হয় আর যা দাঁড়াবে — তার তর্ক।') },
      { n: '03', title: t('Draw', 'অঙ্কন'), note: t('Millimetres. The promise is kept here or nowhere.', 'মিলিমিটার। প্রতিশ্রুতি এখানেই রক্ষা হয়, নয়তো কোথাও নয়।') },
      { n: '04', title: t('Build', 'নির্মাণ'), note: t('Where the drawing meets weather and people.', 'যেখানে নকশা আবহাওয়া আর মানুষের মুখোমুখি হয়।') },
    ],
    links: [{ label: 'Bikku Bikku', href: 'https://www.facebook.com/bikkubikkucookies/' }],
  },

  maimuna: {
    slug: 'maimuna',
    short: t('Maimuna', 'মাইমুনা'),
    name: t('Mosammat Maimuna Khatun', 'মোসাম্মৎ মাইমুনা খাতুন'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Researcher · Economics', 'গবেষক · অর্থনীতি'),
    tagline: t(
      "Bachelor in Economics and two Master's degrees from Independent University, Bangladesh. Co-founder of Bikku Bikku. Spends her working life turning arguments into evidence.",
      'ইন্ডিপেনডেন্ট ইউনিভার্সিটি, বাংলাদেশ থেকে অর্থনীতিতে স্নাতক ও দুটি স্নাতকোত্তর। বিক্কু বিক্কুর সহ-প্রতিষ্ঠাতা। কর্মজীবন কাটে যুক্তিকে প্রমাণে রূপ দিতে।'
    ),
    speech: t(
      'Everyone already knows what they think before the data arrives. The whole discipline is the habit of letting the numbers say no to you — and saying so out loud afterwards, even when it was your own idea they refused.',
      "উপাত্ত আসার আগেই সবাই জানে তারা কী ভাবে। গোটা বিদ্যাটাই এই অভ্যাসের — সংখ্যাকে আপনাকে 'না' বলতে দেওয়া, আর পরে সেটা প্রকাশ্যে বলা, এমনকি যখন সেই 'না' আপনার নিজের ধারণাকেই বলা হয়েছে।"
    ),
    roles: [
      { icon: '📊', title: t('Researcher', 'গবেষক'), org: t('Economics', 'অর্থনীতি'),
        note: t('Evidence, method, and the patience to be proved wrong.', 'প্রমাণ, পদ্ধতি, আর ভুল প্রমাণিত হওয়ার ধৈর্য।') },
      { icon: '🍪', title: t('Co-Founder · Bikku Bikku', 'সহ-প্রতিষ্ঠাতা · বিক্কু বিক্কু'), org: t('Homemade cookies', 'ঘরে বানানো কুকি'),
        note: t('Baked fresh, from scratch, every time.', 'প্রতিবারই একেবারে শুরু থেকে, তাজা বেক করা।') },
      { icon: '🏛️', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
    ],
    figures: [
      { n: 3, label: t('Degrees', 'ডিগ্রি'), sub: t('One bachelor, two masters', 'এক স্নাতক, দুই স্নাতকোত্তর') },
      { n: 4, label: t('Languages', 'ভাষা'), sub: t('Bangla, English, French, Arabic', 'বাংলা, ইংরেজি, ফরাসি, আরবি') },
      { n: 6, label: t('Research skills', 'গবেষণা দক্ষতা'), sub: t('Analysis to fieldwork', 'বিশ্লেষণ থেকে মাঠকর্ম') },
      { n: 2, label: t('Roles held', 'দায়িত্ব'), sub: t('Researcher and co-founder', 'গবেষক ও সহ-প্রতিষ্ঠাতা') },
    ],
    education: [
      { school: 'Independent University, Bangladesh', where: t('Bachelor · Economics', 'স্নাতক · অর্থনীতি') },
      { school: 'Independent University, Bangladesh', where: t("Master's", 'স্নাতকোত্তর') },
      { school: 'Independent University, Bangladesh', where: t("Second Master's", 'দ্বিতীয় স্নাতকোত্তর') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('French', 'ফরাসি'), level: t('Intermediate', 'মাধ্যমিক'), v: 60 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 45 },
    ],
    focus: [
      t('Economic analysis', 'অর্থনৈতিক বিশ্লেষণ'), t('Statistics', 'পরিসংখ্যান'),
      t('Survey design', 'জরিপ নকশা'), t('Data cleaning', 'উপাত্ত পরিশোধন'),
      t('Academic writing', 'একাডেমিক লেখা'), t('Field research', 'মাঠ গবেষণা'),
    ],
    method: [
      { n: '01', title: t('Question', 'প্রশ্ন'), note: t('Narrow enough that an answer would mean something.', 'এতটা নির্দিষ্ট, যেন উত্তরটির অর্থ থাকে।') },
      { n: '02', title: t('Collect', 'সংগ্রহ'), note: t('The slow part nobody puts in the paper.', 'ধীর অংশটি, যা কেউ গবেষণাপত্রে লেখে না।') },
      { n: '03', title: t('Test', 'পরীক্ষা'), note: t('Let the numbers say no.', 'সংখ্যাকে না বলতে দিন।') },
      { n: '04', title: t('Publish', 'প্রকাশ'), note: t('Say it out loud, including when you were wrong.', 'প্রকাশ্যে বলুন — ভুল ছিলেন তখনও।') },
    ],
    links: [{ label: 'Bikku Bikku', href: 'https://www.facebook.com/bikkubikkucookies/' }],
  },

  anas: {
    slug: 'anas',
    short: t('Anas', 'আনাস'),
    name: t('Mohammed Azizur Rahman Anas', 'মোহাম্মদ আজিজুর রহমান আনাস'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Pilot', 'পাইলট'),
    tagline: t(
      'Pilot, trained at the Malaysian Flying Academy. Marketing Officer at Al-Wawah. Spends his working life somewhere between a checklist and a horizon.',
      'পাইলট, মালয়েশিয়ান ফ্লাইং একাডেমিতে প্রশিক্ষিত। আল-ওয়াওয়াহর মার্কেটিং অফিসার। কর্মজীবন কাটে চেকলিস্ট আর দিগন্তের মাঝামাঝি কোথাও।'
    ),
    speech: t(
      'Flying is not the thrill people imagine. It is the same checklist, read the same way, on the good days and the bad ones — and that is exactly why it is safe. The discipline is the romance. The view is a bonus.',
      'ওড়া মানে সেই রোমাঞ্চ নয়, যা মানুষ কল্পনা করে। এটি একই চেকলিস্ট, একইভাবে পড়া — ভালো দিনেও, খারাপ দিনেও। আর ঠিক সে কারণেই এটি নিরাপদ। নিয়মানুবর্তিতাই এখানে রোমান্স। দৃশ্যটা উপরি পাওনা।'
    ),
    roles: [
      { icon: '✈️', title: t('Pilot', 'পাইলট'), org: 'Malaysian Flying Academy Sdn Bhd' },
      { icon: '📣', title: t('Marketing Officer', 'মার্কেটিং অফিসার'), org: 'Al-Wawah',
        note: t("Bringing people through the door of the family's cafe.", 'পরিবারের ক্যাফের দরজা দিয়ে মানুষ নিয়ে আসা।') },
      { icon: '🏛️', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
    ],
    education: [
      { school: 'Mastermind School', where: t('Dhaka', 'ঢাকা') },
      { school: 'Malaysian Flying Academy', where: t('Sdn Bhd · Malaysia', 'এসডিএন বিএইচডি · মালয়েশিয়া') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 45 },
      { name: t('Malay', 'মালয়'), level: t('Basic', 'প্রাথমিক'), v: 30 },
    ],
    focus: [
      t('Flight operations', 'ফ্লাইট পরিচালনা'), t('Navigation', 'নেভিগেশন'),
      t('Standard procedures', 'প্রমিত প্রক্রিয়া'), t('Crew coordination', 'ক্রু সমন্বয়'),
      t('Brand marketing', 'ব্র্যান্ড মার্কেটিং'), t('Customer outreach', 'গ্রাহক সংযোগ'),
    ],
    checklist: [
      { n: '01', title: t('Preflight', 'প্রি-ফ্লাইট'), note: t('Walk the aircraft. Trust nothing you have not seen.', 'উড়োজাহাজ ঘুরে দেখুন। যা নিজে দেখেননি, তাতে ভরসা নয়।') },
      { n: '02', title: t('Departure', 'উড্ডয়ন'), note: t('The same words, in the same order, every time.', 'একই শব্দ, একই ক্রমে, প্রতিবার।') },
      { n: '03', title: t('Cruise', 'ক্রুজ'), note: t('Watch the instruments, not the view.', 'দৃশ্য নয়, যন্ত্রের দিকে তাকান।') },
      { n: '04', title: t('Approach', 'অবতরণ'), note: t('Stabilised, or go around. There is no third option.', 'স্থিতিশীল, নয়তো আবার ঘুরে আসা। তৃতীয় কোনো পথ নেই।') },
    ],
    links: [{ label: 'alwawah.com', href: 'https://alwawah.com' }],
  },

  arafat: {
    slug: 'arafat',
    short: t('Arafat', 'আরাফাত'),
    name: t('Mohammed Ahmadur Rahman Arafat', 'মোহাম্মদ আহমাদুর রহমান আরাফাত'),
    role: t('Director · Khandani Legacy', 'পরিচালক · খানদানি লিগ্যাসি'),
    profession: t('Homesteader', 'হোমস্টেডার'),
    tagline: t(
      'Co-founder of Bikku Bikku. Works at the pace things actually grow at, which is slower than anybody would like and exactly as fast as it needs to be.',
      'বিক্কু বিক্কুর সহ-প্রতিষ্ঠাতা। কাজ করেন সেই গতিতে, যে গতিতে জিনিস সত্যিই বাড়ে — যা সবার চাওয়ার চেয়ে ধীর, আর ঠিক ততটাই দ্রুত যতটা দরকার।'
    ),
    speech: t(
      'Nothing on a homestead can be hurried, and that is the whole lesson. You do the work in the right week or you do without for the year. Everything else I have ever been taught about patience was theory next to that.',
      'খামারে কোনো কিছুই তাড়াহুড়ো করা যায় না, আর এটাই পুরো শিক্ষা। ঠিক সপ্তাহে কাজটি করবেন, নয়তো সারা বছর তা ছাড়াই কাটাবেন। ধৈর্য নিয়ে আর যা কিছু শিখেছি, তার পাশে সেসব কেবল তত্ত্ব।'
    ),
    roles: [
      { icon: '🌱', title: t('Homesteading', 'হোমস্টেডিং'), org: t('The land', 'জমি'),
        note: t('Growing, keeping, mending — the work that does not announce itself.', 'ফলানো, দেখাশোনা, মেরামত — যে কাজ নিজের কথা জানান দেয় না।') },
      { icon: '🍪', title: t('Co-Founder · Bikku Bikku', 'সহ-প্রতিষ্ঠাতা · বিক্কু বিক্কু'), org: t('Homemade cookies', 'ঘরে বানানো কুকি'),
        note: t('Baked from scratch, fresh, for anytime munching.', 'একেবারে শুরু থেকে বানানো, তাজা — যখন খুশি খাওয়ার জন্য।') },
      { icon: '🏡', title: t('Director', 'পরিচালক'), org: 'Khandani Legacy' },
    ],
    seasons: [
      { icon: '🌱', label: t('Sow', 'বপন'), note: t('The week you cannot miss.', 'যে সপ্তাহ হাতছাড়া করা যায় না।') },
      { icon: '🌿', label: t('Tend', 'পরিচর্যা'), note: t('The long, unglamorous middle.', 'দীর্ঘ, নিরাভরণ মাঝপথ।') },
      { icon: '🌾', label: t('Harvest', 'ফসল'), note: t('What the patience was for.', 'ধৈর্যটা যার জন্য ছিল।') },
      { icon: '🍪', label: t('Bake', 'বেকিং'), note: t('And then it becomes cookies.', 'তারপর তা কুকি হয়ে ওঠে।') },
    ],
    education: [
      { school: 'Mastermind School', where: t('Dhaka', 'ঢাকা') },
      { school: 'Yell International', where: t('Further study', 'উচ্চতর পড়াশোনা') },
    ],
    languages: [
      { name: t('Bangla', 'বাংলা'), level: t('Mother tongue', 'মাতৃভাষা'), v: 100 },
      { name: t('English', 'ইংরেজি'), level: t('Fluent', 'সাবলীল'), v: 95 },
      { name: t('French', 'ফরাসি'), level: t('Intermediate', 'মাধ্যমিক'), v: 60 },
      { name: t('Arabic', 'আরবি'), level: t('Quran reading', 'কুরআন পাঠ'), v: 45 },
    ],
    focus: [
      t('Growing', 'চাষ'), t('Animal keeping', 'পশুপালন'), t('Preserving', 'সংরক্ষণ'),
      t('Repair and maintenance', 'মেরামত ও রক্ষণাবেক্ষণ'), t('Baking', 'বেকিং'), t('Small-batch production', 'অল্প পরিমাণে উৎপাদন'),
    ],
    links: [{ label: 'Bikku Bikku', href: 'https://www.facebook.com/bikkubikkucookies/' }],
  },
};

export const UI = {
  words: t('In their words', 'তাঁদের ভাষায়'),
  wordsHis: t('In his words', 'তাঁর ভাষায়'),
  wordsHer: t('In her words', 'তাঁর ভাষায়'),
  positions: t('Positions', 'দায়িত্ব'),
  practice: t('Practice', 'পেশা'),
  work: t('Work', 'কাজ'),
  education: t('Education', 'শিক্ষা'),
  languages: t('Languages', 'ভাষা'),
  stack: t('Stack', 'প্রযুক্তি'),
  addPhoto: t('Add photo', 'ছবি দিন'),
  scroll: t('Scroll', 'স্ক্রল'),
  light: t('Light', 'আলো'),
  dark: t('Dark', 'অন্ধকার'),
};
