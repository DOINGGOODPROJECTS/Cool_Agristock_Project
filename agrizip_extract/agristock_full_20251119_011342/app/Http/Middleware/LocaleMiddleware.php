<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from session, or user preference, or default to 'fr'
        $locale = session('locale');
        
        if (!$locale && auth()->check() && auth()->user()->language) {
            $locale = auth()->user()->language;
        }
        
        if (!$locale) {
            $locale = config('app.locale', 'fr');
        }

        // Save to session
        session()->put('locale', $locale);
        
        // Save to user if authenticated
        if (auth()->check() && auth()->user()->language !== $locale) {
            auth()->user()->update(['language' => $locale]);
        }

        // Set application locale
        app()->setLocale($locale);
        
        return $next($request);
    }
}
