<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the UI language: the user's saved locale wins over the session
 * locale, which wins over the application default (English).
 *
 * Registered in the "web" group (which also covers Livewire update requests)
 * and in the panel middleware stacks. Do not register panel middleware as
 * Livewire-persistent: that re-runs the cookie and session middleware on
 * every Livewire request and logs the user out.
 */
class SetLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->user()?->locale ?? $request->session()->get('locale');

        if (is_string($locale) && array_key_exists($locale, config('app.available_locales', []))) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
