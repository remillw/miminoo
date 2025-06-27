<?php

namespace App\Services;

use App\Models\Ad;
use App\Models\User;
use App\Notifications\NewAnnouncementInRadius;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class AnnouncementNotificationService
{
    /**
     * Notifier les babysitters dans le rayon de la nouvelle annonce
     */
    public function notifyBabysittersInRadius(Ad $ad): void
    {
        try {
            // Récupérer les coordonnées de l'annonce
            $adLatitude = $ad->address->latitude;
            $adLongitude = $ad->address->longitude;

            Log::info('Recherche babysitters dans le rayon pour annonce', [
                'ad_id' => $ad->id,
                'ad_latitude' => $adLatitude,
                'ad_longitude' => $adLongitude
            ]);

            // Récupérer tous les babysitters vérifiés et disponibles avec leurs adresses
            $babysitters = User::whereHas('roles', function($query) {
                    $query->where('name', 'babysitter');
                })
                ->whereHas('babysitterProfile', function($query) {
                    $query->where('verification_status', 'verified')
                          ->where('is_available', true);
                })
                ->whereNotNull('address_id')
                ->with(['address', 'babysitterProfile'])
                ->get();

            Log::info('Babysitters trouvés', ['count' => $babysitters->count()]);

            $notifiedCount = 0;

            foreach ($babysitters as $babysitter) {
                if (!$babysitter->address) {
                    continue;
                }

                // Calculer la distance avec la formule haversine
                $distance = $this->calculateDistance(
                    $adLatitude,
                    $adLongitude,
                    $babysitter->address->latitude,
                    $babysitter->address->longitude
                );

                // Récupérer le rayon d'intervention (par défaut 25km)
                $maxRadius = $babysitter->babysitterProfile->available_radius_km ?? 25;

                Log::debug('Distance calculée', [
                    'babysitter_id' => $babysitter->id,
                    'distance' => $distance,
                    'max_radius' => $maxRadius
                ]);

                // Si dans le rayon, envoyer la notification
                if ($distance <= $maxRadius) {
                    try {
                        $babysitter->notify(new NewAnnouncementInRadius($ad, $distance));
                        $notifiedCount++;

                        Log::debug('Notification envoyée', [
                            'babysitter_id' => $babysitter->id,
                            'distance' => round($distance, 1)
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur envoi notification babysitter', [
                            'babysitter_id' => $babysitter->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            Log::info('Notifications annonce envoyées', [
                'ad_id' => $ad->id,
                'total_babysitters' => $babysitters->count(),
                'notified_count' => $notifiedCount
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur service notification annonce', [
                'ad_id' => $ad->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Calculer la distance entre deux points avec la formule haversine
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);

        $c = 2 * atan2(sqrt($a), sqrt(1-$a));

        return $earthRadius * $c;
    }
} 