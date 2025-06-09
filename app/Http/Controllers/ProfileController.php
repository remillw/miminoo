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
    public function show()
    {
        $user = Auth::user()->load(['role', 'address', 'parentProfile', 'babysitterProfile']);
        
        // Vérification que l'utilisateur a un rôle
        if (!$user->role) {
            return redirect()->route('dashboard')->with('error', 'Votre compte n\'a pas de rôle assigné. Contactez l\'administrateur.');
        }
        
        $profileData = [
            'user' => $user,
            'role' => $user->role->name,
        ];

        // Si c'est un parent, on charge ses enfants
        if ($user->role->name === 'parent' && $user->parentProfile) {
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

        // Si c'est un parent, mise à jour des enfants
        if ($user->role->name === 'parent' && $request->has('children')) {
            $user->parentProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'children_count' => count($request->children),
                    'children_ages' => $request->children,
                ]
            );
        }

        return redirect()->back()->with('success', 'Profil mis à jour avec succès !');
    }
}
