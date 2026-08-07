<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switches the site's language and returns the visitor to where they were.
     *
     * Open to guests on purpose: the sign-in and account-claim pages are the
     * first thing the family sees, and needing an account before you can read
     * the site in your own language would defeat the point.
     */
    public function update(Request $request, string $locale)
    {
        abort_unless(array_key_exists($locale, SetLocale::SUPPORTED), 404);

        $request->session()->put('locale', $locale);

        return back();
    }
}
