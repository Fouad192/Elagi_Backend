<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
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
        $locale = $request->header('Accept-Language');

        // Check if the application supports the requested locale
        if (in_array($locale, config('app.supported_locales'))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
