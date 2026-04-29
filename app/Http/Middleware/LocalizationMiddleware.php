<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $local = ($request->hasHeader('Accept-Language')) ? $request->header('Accept-Language') : 'en';
        
        // Take only the first two characters (e.g., en-US -> en)
        $local = substr($local, 0, 2);
        
        $supportedLocales = ['en', 'ar'];
        if (in_array($local, $supportedLocales)) {
            App::setLocale($local);
        } else {
            App::setLocale('en');
        }

        return $next($request);
    }
}
