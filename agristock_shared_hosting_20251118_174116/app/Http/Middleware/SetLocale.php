<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if locale is in session
        if (session()->has('locale')) {
            app()->setLocale(session()->get('locale'));
        }
        // Check if user is authenticated and has language preference
        elseif (auth()->check() && auth()->user()->language) {
            app()->setLocale(auth()->user()->language);
            session()->put('locale', auth()->user()->language);
        }
        // Default to French
        else {
            app()->setLocale('fr');
            session()->put('locale', 'fr');
        }

        return $next($request);
    }
}
