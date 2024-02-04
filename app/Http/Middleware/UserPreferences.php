<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Locale;

class UserPreferences extends Middleware
{
    public function handle($request, Closure $next)
    {
        $defaultLocale = Locale::getPrimaryLanguage(Locale::getDefault()) . '_' . Locale::getRegion(Locale::getDefault());
        Session::put('locale', $request->user()->locale ?? $defaultLocale);

        Session::put('timezone', $request->user()->timezone ?? config('app.timezone'));
        // $language = Locale::getPrimaryLanguage($userLocale ?? config('app.locale'));

        /*
         * Set application locale which will be used as application language for translations
         */
        app()->setLocale($request->user()->locale ?? config('app.locale'));

        /**
         * set date time locale
         */
        // $ip = request()->server->get('REMOTE_ADDR');
        // $geoIpInfo = geoip()->getLocation($ip);
        $dateLocale = $request->user()->locale_date ?? $defaultLocale;
        // $timeLocale = $request->user()->locale_time ?? $defaultLocale;
        Session::put('date_locale', $dateLocale);
        // Session::put('time_locale', $timeLocale);
        Carbon::setLocale($dateLocale);

        /*
         * set number formatting locale
         */
        // Date::setLocale($request->user()->number_locale ?? config('app.locale'));
        Session::put('number_locale', $request->user()->locale_number ?? $defaultLocale);

        return $next($request);
    }
}
