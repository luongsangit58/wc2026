<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /** Supported UI locales. */
    public const LOCALES = ['en' => 'English', 'vi' => 'Tiếng Việt'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'en'));

        if (! array_key_exists($locale, self::LOCALES)) {
            $locale = 'en';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
