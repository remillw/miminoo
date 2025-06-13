<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ad;
use App\Models\BabysitterProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function dashboard()
    {
        // Statistiques pour le dashboard
        $stats = [
            'total_users' => User::count(),
            'total_babysitters' => User::whereHas('roles', function($q) {
                $q->where('name', 'babysitter');
            })->count(),
            'pending_verifications' => BabysitterProfile::where('verification_status', 'pending')->count(),
            'verified_babysitters' => BabysitterProfile::where('verification_status', 'verified')->count(),
            'total_ads' => Ad::count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats
        ]);
    }
}
