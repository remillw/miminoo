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

            $babysitters = User::whereHas('roles', function($query) {
                    $query->where('name', 'babysitter');
                })
                ->whereHas('babysitterProfile', function($query) {
                    $query->where('verification_status', 'verified')
                          ->where('is_available', true); // Filtre: seulement les babysitters disponibles
                })
                ->whereNotNull('address_id')
                ->where('email_notifications', true) // Filtre: seulement ceux qui ont activé les notifications email
                ->with(['address', 'babysitterProfile'])
                ->get();

            Log::info('Babysitters trouvés avec notifications email activées', [
                'count' => $babysitters->count(),
                'filter_email_notifications' => true,
                'total_babysitters_before_email_filter' => User::whereHas('roles', function($query) {
                        $query->where('name', 'babysitter');
                    })
                    ->whereHas('babysitterProfile', function($query) {
                        $query->where('verification_status', 'verified')
                              ->where('is_available', true);
                    })
                    ->whereNotNull('address_id')
                    ->count()
            ]);

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
                        Log::info('Envoi notification babysitter', [
                            'babysitter_id' => $babysitter->id,
                            'babysitter_email' => $babysitter->email,
                            'distance' => round($distance, 1),
                            'max_radius' => $maxRadius
                        ]);
                        
                        $notification = new NewAnnouncementInRadius($ad, $distance);
                        $babysitter->notify($notification);
                        
                        // Envoyer la notification push manuellement
                        if ($babysitter->device_token && $babysitter->push_notifications) {
                            try {
                                $pushResult = $notification->toExpo($babysitter);
                                Log::info('Notification push envoyée', [
                                    'babysitter_id' => $babysitter->id,
                                    'push_result' => $pushResult
                                ]);
                            } catch (\Exception $pushError) {
                                Log::error('Erreur notification push', [
                                    'babysitter_id' => $babysitter->id,
                                    'error' => $pushError->getMessage()
                                ]);
                            }
                        }
                        
                        $notifiedCount++;

                        Log::info('Notification envoyée avec succès', [
                            'babysitter_id' => $babysitter->id,
                            'babysitter_email' => $babysitter->email
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Erreur envoi notification babysitter', [
                            'babysitter_id' => $babysitter->id,
                            'babysitter_email' => $babysitter->email,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                } else {
                    Log::debug('Babysitter hors rayon', [
                        'babysitter_id' => $babysitter->id,
                        'distance' => round($distance, 1),
                        'max_radius' => $maxRadius,
                        'babysitter_city' => $babysitter->address->address ?? 'N/A',
                        'ad_city' => $ad->address->address ?? 'N/A'
                    ]);
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