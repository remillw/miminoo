<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\ParentProfile;
use App\Models\BabysitterProfile;
use App\Models\Language;
use App\Models\Skill;
use App\Models\AgeRange;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = Auth::user()->load([
            'roles', 
            'address', 
            'parentProfile', 
            'babysitterProfile.languages',
            'babysitterProfile.skills',
            'babysitterProfile.excludedAgeRanges',
            'babysitterProfile.experiences'
        ]);
        
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
            'user' => array_merge($user->toArray(), [
                'social_data_locked' => $user->social_data_locked,
                'provider' => $user->provider,
                'is_social_account' => $user->is_social_account,
                'avatar_url' => $user->getAvatarUrl(),
            ]),
            'userRoles' => $userRoles,
            'hasParentRole' => in_array('parent', $userRoles),
            'hasBabysitterRole' => in_array('babysitter', $userRoles),
            'requestedMode' => $currentMode, // Mode demandé via URL
        ];

        // Charger les données selon les rôles de l'utilisateur
        // Si l'utilisateur a le rôle parent, on charge ses enfants
        if (in_array('parent', $userRoles) && $user->parentProfile) {
            $profileData['children'] = $user->parentProfile->children_ages ?? [];
        }

        // Si l'utilisateur a le rôle babysitter, on charge toujours son profil babysitter et les options
        // (même s'il est actuellement en mode parent, les données doivent être disponibles pour le switch)
        if (in_array('babysitter', $userRoles)) {
            $babysitterProfile = $user->babysitterProfile;
            if ($babysitterProfile) {
                // Ajouter les URLs des photos supplémentaires
                $babysitterProfile->additional_photos_urls = $user->getAdditionalPhotosUrls();
            }
            $profileData['babysitterProfile'] = $babysitterProfile;
            $profileData['availableLanguages'] = Language::where('is_active', true)->get();
            $profileData['availableSkills'] = Skill::where('is_active', true)->get();
            $profileData['availableAgeRanges'] = AgeRange::where('is_active', true)->orderBy('display_order')->get();
        }

        // Ajouter la clé API Google Places pour le frontend
        $profileData['googlePlacesApiKey'] = config('services.google.places_api_key');

        return Inertia::render('profil', $profileData);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'date_of_birth' => 'nullable|date|before:' . now()->subYears(16)->format('Y-m-d'),
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
            
            // Champs profil babysitter
            'bio' => 'nullable|string|max:2000',
            'experience_years' => 'nullable|integer|min:0|max:50',
            'available_radius_km' => 'nullable|integer|min:1|max:100',
            'hourly_rate' => 'nullable|numeric|min:0|max:100',
            'is_available' => 'nullable|boolean',
            'has_driving_license' => 'nullable|boolean',
            'has_vehicle' => 'nullable|boolean',
            'comfortable_with_all_ages' => 'nullable|boolean',
            'language_ids' => 'nullable|array',
            'language_ids.*' => 'exists:languages,id',
            'skill_ids' => 'nullable|array',
            'skill_ids.*' => 'exists:skills,id',
            'excluded_age_range_ids' => 'nullable|array',
            'excluded_age_range_ids.*' => 'exists:age_ranges,id',
            'experiences' => 'nullable|array',
            'experiences.*.type' => 'required|in:formation,experience',
            'experiences.*.title' => 'required|string|max:255',
            'experiences.*.description' => 'nullable|string|max:1000',
            'experiences.*.institution' => 'nullable|string|max:255',
            'experiences.*.start_date' => 'nullable|date',
            'experiences.*.end_date' => 'nullable|date|after_or_equal:start_date',
            'experiences.*.is_current' => 'nullable|boolean',
            'avatar' => 'nullable|string', // Base64 encoded image pour l'avatar
            'profile_photos' => 'nullable|array|max:6',
            'profile_photos.*' => 'string', // Base64 encoded images pour les photos supplémentaires
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

        // Validation spéciale pour les babysitters
        if ($request->mode === 'babysitter' && $request->date_of_birth) {
            $age = \Carbon\Carbon::parse($request->date_of_birth)->age;
            if ($age < 16) {
                return back()->withErrors(['date_of_birth' => 'Vous devez avoir au moins 16 ans pour être babysitter.']);
            }
        }

        // Mise à jour des informations utilisateur
        $userData = [];
        
        // Empêcher la modification des données pour les utilisateurs Google
        if (!($user->google_id && !$user->password)) {
            $userData['firstname'] = $request->firstname;
            $userData['lastname'] = $request->lastname;
            $userData['email'] = $request->email;
        }
        
        if ($request->has('date_of_birth')) {
            $userData['date_of_birth'] = $request->date_of_birth;
        }
        
        // Gestion de l'avatar (photo de profil principale)
        if ($request->has('avatar') && $request->avatar) {
            // Supprimer l'ancien avatar s'il existe
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $avatarPath = $this->saveBase64Image($request->avatar, 'avatars/' . $user->id);
            if ($avatarPath) {
                $userData['avatar'] = $avatarPath;
            }
        }
        
        if (!empty($userData)) {
            $user->update($userData);
        }

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

        // Si on est en mode babysitter ET que l'utilisateur a le rôle babysitter, mise à jour du profil babysitter
        if ($request->mode === 'babysitter' && in_array('babysitter', $user->roles()->pluck('name')->toArray())) {
            $babysitterData = [];
            
            if ($request->has('bio')) {
                $babysitterData['bio'] = $request->bio;
            }
            if ($request->has('experience_years')) {
                $babysitterData['experience_years'] = $request->experience_years;
            }
            if ($request->has('available_radius_km')) {
                $babysitterData['available_radius_km'] = $request->available_radius_km;
            }
            if ($request->has('hourly_rate')) {
                $babysitterData['hourly_rate'] = $request->hourly_rate;
            }
            if ($request->has('is_available')) {
                $babysitterData['is_available'] = $request->is_available;
            }
            if ($request->has('has_driving_license')) {
                $babysitterData['has_driving_license'] = $request->has_driving_license;
            }
            if ($request->has('has_vehicle')) {
                $babysitterData['has_vehicle'] = $request->has_vehicle;
            }
            if ($request->has('comfortable_with_all_ages')) {
                $babysitterData['comfortable_with_all_ages'] = $request->comfortable_with_all_ages;
            }
            
            // Gestion des photos supplémentaires
            if ($request->has('profile_photos')) {
                // Supprimer les anciennes photos s'il y en a
                $babysitterProfile = $user->babysitterProfile;
                if ($babysitterProfile && $babysitterProfile->profile_photos) {
                    foreach ($babysitterProfile->profile_photos as $oldPhoto) {
                        if (!str_starts_with($oldPhoto, 'data:image')) {
                            Storage::disk('public')->delete($oldPhoto);
                        }
                    }
                }
                
                $photoPaths = [];
                foreach ($request->profile_photos as $photo) {
                    $photoPath = $this->saveBase64Image($photo, 'profile_photos/' . $user->id);
                    if ($photoPath) {
                        $photoPaths[] = $photoPath;
                    }
                }
                $babysitterData['profile_photos'] = $photoPaths;
            }

            $babysitterProfile = $user->babysitterProfile()->updateOrCreate(
                ['user_id' => $user->id],
                $babysitterData
            );

            // Gestion des relations many-to-many
            if ($request->has('language_ids')) {
                $babysitterProfile->languages()->sync($request->language_ids);
            }
            if ($request->has('skill_ids')) {
                $babysitterProfile->skills()->sync($request->skill_ids);
            }
            if ($request->has('excluded_age_range_ids')) {
                $babysitterProfile->excludedAgeRanges()->sync($request->excluded_age_range_ids);
            }

            // Gestion des expériences
            if ($request->has('experiences')) {
                // Supprimer les anciennes expériences
                $babysitterProfile->experiences()->delete();
                
                // Ajouter les nouvelles expériences
                foreach ($request->experiences as $experienceData) {
                    if (!empty($experienceData['title'])) {
                        $babysitterProfile->experiences()->create($experienceData);
                    }
                }
            }
        }

        // Rediriger avec le mode s'il est fourni
        $redirectUrl = route('profil');
        if ($request->mode) {
            $redirectUrl .= '?mode=' . $request->mode;
        }

        return redirect($redirectUrl)->with('success', 'Profil mis à jour avec succès !');
    }
    
    /**
     * Sauvegarder une image base64 dans le storage
     */
    private function saveBase64Image($base64String, $folder)
    {
        try {
            // Vérifier si c'est bien une image base64
            if (!preg_match('/^data:image\/(\w+);base64,/', $base64String, $matches)) {
                return null;
            }
            
            $imageType = $matches[1];
            $base64String = substr($base64String, strpos($base64String, ',') + 1);
            $imageData = base64_decode($base64String);
            
            if ($imageData === false) {
                return null;
            }
            
            // Générer un nom de fichier unique
            $fileName = uniqid() . '.' . $imageType;
            $filePath = $folder . '/' . $fileName;
            
            // Sauvegarder le fichier
            Storage::disk('public')->put($filePath, $imageData);
            
            return $filePath;
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la sauvegarde de l\'image: ' . $e->getMessage());
            return null;
        }
    }
}
