<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\ParentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user()->load(['roles', 'address', 'parentProfile', 'babysitterProfile']);
        
        // Récupérer tous les rôles de l'utilisateur
        $userRoles = $user->roles()->pluck('name')->toArray();
        
        // Vérification que l'utilisateur a au moins un rôle
        if (empty($userRoles)) {
            return redirect()->route('dashboard')->with('error', 'Votre compte n\'a pas de rôle assigné. Contactez l\'administrateur.');
        }
        
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
        
        $profileData = [
            'user' => $user,
            'userRoles' => $userRoles,
            'hasParentRole' => in_array('parent', $userRoles),
            'hasBabysitterRole' => in_array('babysitter', $userRoles),
            'requestedMode' => $currentMode, // Mode demandé via URL
        ];

        // Si c'est en mode parent ET qu'il a le rôle parent, on charge ses enfants
        $effectiveMode = $currentMode ?: (in_array('parent', $userRoles) ? 'parent' : 'babysitter');
        if ($effectiveMode === 'parent' && in_array('parent', $userRoles) && $user->parentProfile) {
            $profileData['children'] = $user->parentProfile->children_ages ?? [];
        }

        return Inertia::render('profil', $profileData);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'address' => 'required|string',
            'postal_code' => 'required|string',
            'country' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'google_place_id' => 'nullable|string',
            'children' => 'array|nullable',
            'children.*.nom' => 'required|string',
            'children.*.age' => 'required|string',
            'children.*.unite' => 'required|in:ans,mois',
            'mode' => 'nullable|string|in:parent,babysitter',
        ]);

        // Mise à jour ou création de l'adresse
        if ($user->address_id) {
            $user->address->update([
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'google_place_id' => $request->google_place_id,
            ]);
        } else {
            $address = Address::create([
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'country' => $request->country,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'google_place_id' => $request->google_place_id,
            ]);
            
            $user->update(['address_id' => $address->id]);
        }

        // Mise à jour des informations utilisateur
        $user->update([
            'firstname' => $request->firstname,
            'lastname' => $request->lastname,
            'email' => $request->email,
        ]);

        // Si on est en mode parent ET que l'utilisateur a le rôle parent, mise à jour des enfants
        if (($request->mode === 'parent' || in_array('parent', $user->roles()->pluck('name')->toArray())) && $request->has('children')) {
            $user->parentProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'children_count' => count($request->children),
                    'children_ages' => $request->children,
                ]
            );
        }

        // Rediriger avec le mode s'il est fourni
        $redirectUrl = route('profil');
        if ($request->mode) {
            $redirectUrl .= '?mode=' . $request->mode;
        }

        return redirect($redirectUrl)->with('success', 'Profil mis à jour avec succès !');
    }
}
