<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Stores the chosen UI language in the session and, for signed-in users, on their profile.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(array_key_exists($locale, config('app.available_locales', [])), 404);

        $request->session()->put('locale', $locale);
        $request->user()?->update(['locale' => $locale]);

        return redirect()->back(fallback: '/');
    }
}
