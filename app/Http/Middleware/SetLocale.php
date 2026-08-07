<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Puts the language the visitor chose back in force on every request.
 *
 * The choice lives in the session rather than on the person's record, so it
 * works on the login and claim pages too — where the family are most likely to
 * need Bangla, being the screens they meet before they have an account at all.
 */
class SetLocale
{
    /** Languages the site is actually translated into. */
    public const SUPPORTED = ['en' => 'English', 'bn' => 'বাংলা'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (is_string($locale) && array_key_exists($locale, static::SUPPORTED)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
