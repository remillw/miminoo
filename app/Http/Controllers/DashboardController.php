<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // Récupérer tous les rôles de l'utilisateur via la relation many-to-many
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        // Récupérer les profils associés
        $parentProfile = $user->parentProfile;
        $babysitterProfile = $user->babysitterProfile;
        
        // Déterminer le mode demandé (via paramètre URL)
        $requestedMode = $request->get('mode');
        $validModes = ['parent', 'babysitter'];
        $currentMode = null;
        
        if ($requestedMode && in_array($requestedMode, $validModes)) {
            // Vérifier que l'utilisateur a bien ce rôle
            if (($requestedMode === 'parent' && in_array('parent', $userRoles)) || 
                ($requestedMode === 'babysitter' && in_array('babysitter', $userRoles))) {
                $currentMode = $requestedMode;
            }
        }
        
        return Inertia::render('Dashboard', [
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'status' => $user->status,
            ],
            'userRoles' => $userRoles,
            'hasParentRole' => in_array('parent', $userRoles),
            'hasBabysitterRole' => in_array('babysitter', $userRoles),
            'requestedMode' => $currentMode, // Mode demandé via URL
            'parentProfile' => $parentProfile,
            'babysitterProfile' => $babysitterProfile,
        ]);
    }
}
