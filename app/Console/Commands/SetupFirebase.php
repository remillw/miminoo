<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class SetupFirebase extends Command
{
    protected $signature = 'firebase:setup';
    protected $description = 'Setup Firebase configuration for push notifications';

    public function handle()
    {
        $this->info("🔧 Configuration Firebase pour les push notifications");
        
        // Vérifier la configuration actuelle
        $this->info("\n📋 Configuration actuelle:");
        $projectId = config('services.firebase.project_id');
        $serviceAccountPath = config('services.firebase.service_account_path');
        
        $this->info("FIREBASE_PROJECT_ID: " . ($projectId ?: 'NON CONFIGURÉ'));
        $this->info("Service Account Path: " . $serviceAccountPath);
        $this->info("Service Account Exists: " . (file_exists($serviceAccountPath) ? 'OUI' : 'NON'));

        // Vérifier le .env
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        if (!$projectId) {
            $this->error("❌ FIREBASE_PROJECT_ID manquant dans .env");
            
            $newProjectId = $this->ask('Entrez votre Firebase Project ID');
            if ($newProjectId) {
                if (strpos($envContent, 'FIREBASE_PROJECT_ID=') !== false) {
                    $envContent = preg_replace('/FIREBASE_PROJECT_ID=.*/', "FIREBASE_PROJECT_ID={$newProjectId}", $envContent);
                } else {
                    $envContent .= "\nFIREBASE_PROJECT_ID={$newProjectId}\n";
                }
                file_put_contents($envPath, $envContent);
                $this->info("✅ FIREBASE_PROJECT_ID ajouté au .env");
            }
        }

        // Vérifier le service account
        if (!file_exists($serviceAccountPath)) {
            $this->error("❌ Fichier service account manquant: {$serviceAccountPath}");
            
            $this->warn("\n📖 Instructions pour télécharger le service account:");
            $this->warn("1. Allez sur https://console.firebase.google.com/");
            $this->warn("2. Sélectionnez votre projet: " . ($projectId ?: '[VOTRE_PROJECT_ID]'));
            $this->warn("3. Paramètres du projet > Comptes de service");
            $this->warn("4. Cliquez sur 'Générer une nouvelle clé privée'");
            $this->warn("5. Téléchargez le fichier JSON");
            
            if ($this->confirm('Avez-vous téléchargé le fichier service account ?')) {
                $filePath = $this->ask('Chemin vers le fichier téléchargé');
                
                if ($filePath && file_exists($filePath)) {
                    // Créer le dossier storage/app si nécessaire
                    $storageDir = dirname($serviceAccountPath);
                    if (!file_exists($storageDir)) {
                        mkdir($storageDir, 0755, true);
                    }
                    
                    copy($filePath, $serviceAccountPath);
                    $this->info("✅ Service account copié vers {$serviceAccountPath}");
                    
                    // Vérifier le contenu du fichier
                    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
                    if ($serviceAccount && isset($serviceAccount['project_id'])) {
                        $this->info("✅ Fichier service account valide");
                        $this->info("   Project ID: " . $serviceAccount['project_id']);
                        $this->info("   Client Email: " . $serviceAccount['client_email']);
                    } else {
                        $this->error("❌ Fichier service account invalide");
                    }
                } else {
                    $this->error("❌ Fichier non trouvé: {$filePath}");
                }
            }
        } else {
            $this->info("✅ Service account trouvé");
            
            // Vérifier le contenu
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
            if ($serviceAccount && isset($serviceAccount['project_id'])) {
                $this->info("   Project ID: " . $serviceAccount['project_id']);
                $this->info("   Client Email: " . $serviceAccount['client_email']);
                
                // Vérifier la cohérence avec .env
                if ($projectId && $serviceAccount['project_id'] !== $projectId) {
                    $this->warn("⚠️  Project ID incohérent:");
                    $this->warn("   .env: {$projectId}");
                    $this->warn("   Service Account: " . $serviceAccount['project_id']);
                }
            }
        }

        // Test de configuration
        if (file_exists($serviceAccountPath) && $projectId) {
            $this->info("\n🧪 Test de la configuration...");
            
            if ($this->confirm('Tester l\'authentification Firebase ?')) {
                $this->call('push:test', [
                    'email' => 'r.bouvant@gmail.com',
                    '--title' => 'Test Configuration Firebase',
                    '--body' => 'Configuration testée avec succès !'
                ]);
            }
        }

        $this->info("\n✅ Configuration Firebase terminée !");
        $this->info("📋 Commandes utiles:");
        $this->info("   php artisan push:test r.bouvant@gmail.com");
        $this->info("   php artisan push:test-announcement r.bouvant@gmail.com");
    }
}