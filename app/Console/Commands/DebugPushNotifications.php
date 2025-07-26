<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Ad;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class DebugPushNotifications extends Command
{
    protected $signature = 'push:debug {email?}';
    protected $description = 'Debug push notifications configuration and logs';

    public function handle()
    {
        $email = $this->argument('email') ?: 'r.bouvant@gmail.com';
        
        $this->info("🔍 Debug des push notifications pour: {$email}");
        
        // 1. Vérifier l'utilisateur
        $this->checkUser($email);
        
        // 2. Vérifier la configuration Firebase
        $this->checkFirebaseConfig();
        
        // 3. Vérifier les jobs en queue
        $this->checkQueue();
        
        // 4. Vérifier les logs récents
        $this->checkLogs();
        
        // 5. Vérifier les notifications en base
        $this->checkDatabaseNotifications($email);
    }

    private function checkUser(string $email)
    {
        $this->info("\n👤 Vérification utilisateur:");
        
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("❌ Utilisateur non trouvé: {$email}");
            return;
        }

        $this->info("✅ Utilisateur: {$user->firstname} {$user->lastname} (ID: {$user->id})");
        $this->info("📱 Device token: " . ($user->device_token ? 'Présent (' . substr($user->device_token, 0, 20) . '...)' : 'Absent'));
        $this->info("🔔 Push notifications: " . ($user->push_notifications ? 'Activées' : 'Désactivées'));
        $this->info("📧 Email verified: " . ($user->email_verified_at ? 'Oui' : 'Non'));
        $this->info("📍 Location: " . ($user->babysitter?->city ?: 'Non définie'));
        $this->info("🎯 Radius: " . ($user->babysitter?->radius ?: 'Non défini') . ' km');
    }

    private function checkFirebaseConfig()
    {
        $this->info("\n🔧 Configuration Firebase:");
        
        $projectId = config('services.firebase.project_id');
        $serviceAccountPath = config('services.firebase.service_account_path');
        
        $this->info("Project ID: " . ($projectId ?: '❌ NON CONFIGURÉ'));
        $this->info("Service Account Path: " . $serviceAccountPath);
        $this->info("Service Account Exists: " . (file_exists($serviceAccountPath) ? '✅ OUI' : '❌ NON'));
        
        if (file_exists($serviceAccountPath)) {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            if ($serviceAccount) {
                $this->info("Service Account Project: " . $serviceAccount['project_id']);
                $this->info("Service Account Email: " . $serviceAccount['client_email']);
                
                if ($projectId && $serviceAccount['project_id'] !== $projectId) {
                    $this->error("⚠️  Incohérence Project ID!");
                }
            } else {
                $this->error("❌ Fichier service account invalide");
            }
        }
    }

    private function checkQueue()
    {
        $this->info("\n📤 Vérification Queue:");
        
        try {
            $queueConnection = config('queue.default');
            $this->info("Queue connection: {$queueConnection}");
            
            if ($queueConnection === 'database') {
                $pendingJobs = DB::table('jobs')->count();
                $failedJobs = DB::table('failed_jobs')->count();
                
                $this->info("Jobs en attente: {$pendingJobs}");
                $this->info("Jobs échoués: {$failedJobs}");
                
                if ($failedJobs > 0) {
                    $this->warn("⚠️  Des jobs ont échoué");
                    $recentFailed = DB::table('failed_jobs')
                        ->orderBy('failed_at', 'desc')
                        ->limit(3)
                        ->get(['payload', 'exception', 'failed_at']);
                    
                    foreach ($recentFailed as $failed) {
                        $payload = json_decode($failed->payload, true);
                        $command = $payload['data']['commandName'] ?? 'Unknown';
                        $this->warn("  - {$command} ({$failed->failed_at})");
                    }
                }
                
                // Vérifier les jobs de notification récents
                $recentJobs = DB::table('jobs')
                    ->where('payload', 'like', '%NewAnnouncementInRadius%')
                    ->orWhere('payload', 'like', '%PushNotification%')
                    ->count();
                
                if ($recentJobs > 0) {
                    $this->info("Jobs de notification en attente: {$recentJobs}");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Erreur queue: " . $e->getMessage());
        }
    }

    private function checkLogs()
    {
        $this->info("\n📋 Logs récents (dernières 24h):");
        
        $logFile = storage_path('logs/laravel.log');
        if (!file_exists($logFile)) {
            $this->warn("❌ Fichier de log non trouvé");
            return;
        }
        
        // Lire les dernières lignes du log
        $command = "tail -n 100 {$logFile} | grep -E 'Push notification|Firebase|NewAnnouncementInRadius' | tail -n 10";
        $output = shell_exec($command);
        
        if ($output) {
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                if (strpos($line, 'ERROR') !== false || strpos($line, 'Failed') !== false) {
                    $this->error("  " . $line);
                } elseif (strpos($line, 'successfully') !== false) {
                    $this->info("  " . $line);
                } else {
                    $this->warn("  " . $line);
                }
            }
        } else {
            $this->warn("Aucun log de push notification trouvé");
        }
    }

    private function checkDatabaseNotifications(string $email)
    {
        $this->info("\n📊 Notifications en base:");
        
        $user = User::where('email', $email)->first();
        if (!$user) return;
        
        $notifications = $user->notifications()
            ->where('type', 'App\\Notifications\\NewAnnouncementInRadius')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        $this->info("Notifications totales: " . $notifications->count());
        
        foreach ($notifications as $notification) {
            $data = $notification->data;
            $status = $notification->read_at ? '✅ Lu' : '📬 Non lu';
            $this->info("  - Annonce #{$data['ad_id']} ({$data['distance']}km) - {$status} - {$notification->created_at->format('d/m/Y H:i')}");
        }
        
        // Vérifier les annonces récentes qui auraient dû déclencher des notifications
        $this->info("\n📢 Annonces récentes:");
        $recentAds = Ad::where('created_at', '>=', now()->subDay())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
        
        foreach ($recentAds as $ad) {
            $this->info("  - Annonce #{$ad->id}: {$ad->title} - {$ad->created_at->format('d/m/Y H:i')}");
        }
    }
}