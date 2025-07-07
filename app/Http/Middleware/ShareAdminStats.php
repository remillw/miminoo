<?php

namespace App\Http\Middleware;

use App\Models\BabysitterProfile;
use App\Models\Contact;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareAdminStats
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si c'est une route admin
        if ($request->is('admin') || $request->is('admin/*')) {
            // Partager les stats admin avec toutes les pages d'administration
            Inertia::share('adminStats', function () {
                return [
                    'pending_verifications' => BabysitterProfile::where('verification_status', 'pending')->count(),
                    'unread_contacts' => Contact::where('status', 'unread')->count(),
                ];
            });
        }

        return $next($request);
    }
}
