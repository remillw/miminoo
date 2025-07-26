<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Ad;
use App\Jobs\NotifyBabysittersNewAnnouncement;
use App\Services\AnnouncementNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class TestJobNotification extends Command
{
    protected $signature = 'push:test-job {email} {--ad-id=} {--sync}';
    protected $description = 'Test job notification system for announcements';

    public function handle()
    {
        $email = $this->argument('email');
        $adId = $this->option('ad-id');
        $sync = $this->option('sync');

        $this->info("🔍 Test du système de notification par job pour: {$email}");
        
        // Vérifier l'utilisateur
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé: {$email}");
            return 1;
        }

        $this->info("✅ Utilisateur trouvé: {$user->firstname} {$user->lastname}");
        
        // Vérifier que c'est un babysitter
        $isBabysitter = $user->hasRole('babysitter');
        $this->info("👶 Est babysitter: " . ($isBabysitter ? 'Oui' : 'Non'));
        
        if (!$isBabysitter) {
            $this->error("❌ L'utilisateur n'est pas un babysitter");
            return 1;
        }

        // Vérifier le profil babysitter
        $profile = $user->babysitterProfile;
        if (!$profile) {
            $this->error("❌ Pas de profil babysitter");
            return 1;
        }

        $this->info("✅ Profil babysitter trouvé");
        $this->info("   Verification: " . $profile->verification_status);
        $this->info("   Disponible: " . ($profile->is_available ? 'Oui' : 'Non'));
        $this->info("   Rayon: " . ($profile->available_radius_km ?? 25) . ' km');

        // Vérifier l'adresse
        if (!$user->address) {
            $this->error("❌ Pas d'adresse pour l'utilisateur");
            return 1;
        }

        $this->info("📍 Adresse: " . $user->address->address);
        $this->info("   Coordonnées: " . $user->address->latitude . ', ' . $user->address->longitude);

        // Vérifier les préférences de notification
        $this->info("🔔 Notifications email: " . ($user->email_notifications ? 'Activées' : 'Désactivées'));
        $this->info("📱 Push notifications: " . ($user->push_notifications ? 'Activées' : 'Désactivées'));
        $this->info("📱 Device token: " . ($user->device_token ? 'Présent' : 'Absent'));

        // Activer les notifications si nécessaire
        if (!$user->email_notifications) {
            if ($this->confirm('Activer les notifications email ?')) {
                $user->update(['email_notifications' => true]);
                $this->info("✅ Notifications email activées");
            }
        }

        if (!$user->push_notifications && $user->device_token) {
            if ($this->confirm('Activer les push notifications ?')) {
                $user->update(['push_notifications' => true]);
                $this->info("✅ Push notifications activées");
            }
        }

        // Trouver ou créer une annonce
        $ad = null;
        if ($adId) {
            $ad = Ad::find($adId);
            if (!$ad) {
                $this->error("❌ Annonce non trouvée: {$adId}");
                return 1;
            }
        } else {
            $ad = Ad::latest()->first();
            if (!$ad) {
                $this->error("❌ Aucune annonce en base");
                return 1;
            }
        }

        $this->info("📢 Annonce: {$ad->title} (ID: {$ad->id})");
        if ($ad->address) {
            $this->info("   Lieu: " . $ad->address->address);
            $this->info("   Coordonnées: " . $ad->address->latitude . ', ' . $ad->address->longitude);
        } else {
            $this->error("❌ Annonce sans adresse");
            return 1;
        }

        // Calculer la distance
        $distance = $this->calculateDistance(
            $ad->address->latitude,
            $ad->address->longitude,
            $user->address->latitude,
            $user->address->longitude
        );

        $maxRadius = $profile->available_radius_km ?? 25;
        $this->info("📏 Distance: " . round($distance, 1) . " km (max: {$maxRadius} km)");
        $this->info("🎯 Dans le rayon: " . ($distance <= $maxRadius ? 'Oui' : 'Non'));

        if ($distance > $maxRadius) {
            $this->warn("⚠️  L'utilisateur est hors du rayon, mais on continue le test");
        }

        // Test du service directement
        $this->info("\n🧪 Test du service AnnouncementNotificationService:");
        
        if ($sync || $this->confirm('Tester le service directement (sans queue) ?')) {
            try {
                $service = new AnnouncementNotificationService();
                $service->notifyBabysittersInRadius($ad);
                $this->info("✅ Service exécuté directement");
            } catch (\Exception $e) {
                $this->error("❌ Erreur service: " . $e->getMessage());
            }
        }

        // Test du job
        $this->info("\n📤 Test du job NotifyBabysittersNewAnnouncement:");
        
        if ($this->confirm('Dispatcher le job ?')) {
            try {
                NotifyBabysittersNewAnnouncement::dispatch($ad);
                $this->info("✅ Job dispatché");
                
                $pendingJobs = DB::table('jobs')->count();
                $this->info("📊 Jobs en attente: {$pendingJobs}");
                
                $this->info("📋 Pour traiter le job:");
                $this->info("   php artisan queue:work");
                
            } catch (\Exception $e) {
                $this->error("❌ Erreur job: " . $e->getMessage());
            }
        }

        // Vérifier les logs
        $this->info("\n📋 Vérifiez les logs:");
        $this->info("   tail -f storage/logs/laravel.log | grep -E 'notification|Job|AnnouncementNotificationService'");

        return 0;
    }

    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}