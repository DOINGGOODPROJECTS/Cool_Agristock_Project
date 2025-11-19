<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Switch application locale
     */
    public function switch(Request $request, $locale)
    {
        // Validate locale is supported
        if (!in_array($locale, ['en', 'fr'])) {
            $locale = 'fr'; // Default to French
        }

        // Store locale in session
        session()->put('locale', $locale);
        
        // Also store in user preferences if authenticated
        if (auth()->check()) {
            auth()->user()->update(['language' => $locale]);
        }

        // Redirect back to previous page
        return back();
    }
}
