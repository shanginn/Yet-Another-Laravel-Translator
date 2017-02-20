<?php

namespace Shanginn\Yalt\Http\Middleware;

use Closure;
use App;

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
        // read the language from the request header
        if ($locale = $request->header('Accept-Language')) {
            App::setLocale($locale);
        }

        // get the response after the request is done
        $response = $next($request);

        // set Content Languages header in the response
        if ($locale) {
            $response->headers->set('Content-Language', $locale);
        }

        // return the response
        return $response;
    }
}
