<?php

namespace Shanginn\Yalt\Http\Middleware;

use Carbon\Carbon;
use Closure;
use App;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Yalt;
use Auth;

class Localization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Read the language from Accept-Language header
        $requestLocale = Yalt::getLocaleFromRequest();

        if (($user = Auth::user()) && $user instanceof HasLocalePreference) {
            // Get user saved interface language
            $userInterfaceLocale = $user->preferredLocale();
        }

        // Set the fallback locale
        $fallbackLocale = config('app.locale');

        // Set app to selected language
        if ($locale = $requestLocale ?? $userInterfaceLocale ?? $fallbackLocale) {
            Yalt::setAllLocales($locale);
        }

        // get the response after the request is done
        $response = $next($request);

         // Set Content Languages header in the response
        if ($locale) {
            $response->headers->set('Content-Language', $locale);
        }

        return $response;
    }
}
