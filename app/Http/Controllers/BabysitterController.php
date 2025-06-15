<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AgeRange;
use App\Notifications\BabysitterVerificationRequested;
use App\Notifications\BabysitterVerificationSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BabysitterController extends Controller
{
    public function show($slug)
    {
        // Extraire l'ID du slug (dernière partie après le dernier tiret)
        $parts = explode('-', $slug);
        $userId = end($parts);

        // Vérifier que l'ID est numérique
        if (!is_numeric($userId)) {
            abort(404);
        }

        // Récupérer la babysitter avec toutes ses relations
        $babysitter = User::with([
            'address',
            'babysitterProfile.languages',
            'babysitterProfile.skills', 
            'babysitterProfile.excludedAgeRanges',
            'babysitterProfile.experiences'
        ])
        ->whereHas('roles', function($query) {
            $query->where('name', 'babysitter');
        })
        ->findOrFail($userId);

        if (!$babysitter->babysitterProfile) {
            abort(404, 'Profil babysitter non trouvé');
        }

        // Vérifier que le slug correspond bien à l'utilisateur
        $expectedSlug = $this->createBabysitterSlug($babysitter);
        if ($slug !== $expectedSlug) {
            // Rediriger vers le bon slug
            return redirect()->route('babysitter.show', ['slug' => $expectedSlug]);
        }

        // Récupérer toutes les tranches d'âge disponibles
        $availableAgeRanges = AgeRange::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return Inertia::render('Babysitterprofile', [
            'babysitter' => $babysitter,
            'available_age_ranges' => $availableAgeRanges
        ]);
    }

    public function requestVerification(Request $request)
    {
        $user = $request->user();
        
        // Recharger le profil depuis la base de données pour avoir le statut le plus récent
        $profile = $user->babysitterProfile()->lockForUpdate()->first();

        Log::info('🚀 Demande de vérification reçue', [
            'user_id' => $user->id,
            'user_name' => $user->firstname . ' ' . $user->lastname,
            'current_status' => $profile?->verification_status,
        ]);

        if (!$profile) {
            Log::error('❌ Profil babysitter non trouvé', ['user_id' => $user->id]);
            return response()->json(['message' => 'Profil babysitter non trouvé'], 404);
        }

        // Vérification du statut - empêcher les demandes multiples
        if ($profile->verification_status === 'pending') {
            Log::warning('⚠️ Demande de vérification déjà en cours', [
                'user_id' => $user->id,
                'current_status' => $profile->verification_status
            ]);
            return response()->json(['message' => 'Une demande de vérification est déjà en cours'], 400);
        }

        if ($profile->verification_status === 'verified') {
            Log::warning('⚠️ Profil déjà vérifié', [
                'user_id' => $user->id,
                'current_status' => $profile->verification_status
            ]);
            return response()->json(['message' => 'Votre profil est déjà vérifié'], 400);
        }

        // Notifier tous les administrateurs
        $admins = User::whereHas('roles', function($query) {
            $query->where('name', 'admin');
        })->get();

        Log::info('📧 Notification des administrateurs', ['admin_count' => $admins->count()]);

        foreach ($admins as $admin) {
            $admin->notify(new BabysitterVerificationRequested($user));
        }

        // Notifier le babysitter que sa demande a été envoyée
        $user->notify(new BabysitterVerificationSubmitted());
        
        Log::info('📧 Notification de confirmation envoyée au babysitter', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        // Mettre à jour le statut du profil
        $profile->update(['verification_status' => 'pending']);

        Log::info('✅ Statut mis à jour vers pending', [
            'user_id' => $user->id,
            'new_status' => 'pending'
        ]);

        return response()->json([
            'message' => 'Votre demande de vérification a été envoyée avec succès'
        ]);
    }

    private function isProfileComplete($profile)
    {
        return !!(
            $profile->bio &&
            $profile->experience_years &&
            $profile->available_radius_km &&
            // $profile->hourly_rate && // Rendu optionnel temporairement
            $profile->languages->count() > 0 &&
            $profile->skills->count() > 0 &&
            $profile->age_ranges->count() > 0
        );
    }

    /**
     * Créer un slug pour une babysitter
     */
    private function createBabysitterSlug(User $user): string
    {
        $firstName = $user->firstname ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->firstname)) : 'babysitter';
        $lastName = $user->lastname ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->lastname)) : '';
        
        $slug = trim($firstName . '-' . $lastName . '-' . $user->id, '-');
        return preg_replace('/-+/', '-', $slug);
    }

    /**
     * Toggle la disponibilité du babysitter
     */
    public function toggleAvailability(Request $request)
    {
        $user = $request->user();
        
        // Vérifier que l'utilisateur a un profil babysitter
        $profile = $user->babysitterProfile;
        if (!$profile) {
            return response()->json(['message' => 'Profil babysitter non trouvé'], 404);
        }
        
        // Inverser la disponibilité
        $newAvailability = !$profile->is_available;
        $profile->update(['is_available' => $newAvailability]);
        
        Log::info('Disponibilité babysitter mise à jour', [
            'user_id' => $user->id,
            'user_name' => $user->firstname . ' ' . $user->lastname,
            'new_availability' => $newAvailability
        ]);
        
        return response()->json([
            'success' => true,
            'is_available' => $newAvailability,
            'message' => $newAvailability ? 'Vous êtes maintenant disponible' : 'Vous êtes maintenant indisponible'
        ]);
    }
}
