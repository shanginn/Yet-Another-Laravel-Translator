<?php

namespace Shanginn\Yalt\Http\Middleware;

use Closure;
use App;
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

        // Get user saved interface language
        $userInterfaceLocale = ($user = Auth::user()) ? $user->getLocale() : null;

        // Set app to selected language
        if ($locale = $requestLocale ?? $userInterfaceLocale) {
            App::setLocale($locale);
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
