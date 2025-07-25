<?php

namespace App\Http\Helpers;

use Illuminate\Http\Request;

class MobileDetectionHelper
{
    /**
     * Détecte si la requête provient de l'application mobile Capacitor
     */
    public static function isCapacitorApp(Request $request): bool
    {
        // Vérifier d'abord le paramètre mobile que nous ajoutons
        if ($request->get('mobile') === '1') {
            return true;
        }
        
        $userAgent = $request->header('User-Agent', '');
        
        // Rechercher les indicateurs de Capacitor dans le User-Agent
        $capacitorIndicators = [
            'Capacitor',
            'CapacitorHttp',
            'Mobile/TrouveTaBabysitter',
            'fr.trouvetababysitter.mobile'
        ];
        
        foreach ($capacitorIndicators as $indicator) {
            if (stripos($userAgent, $indicator) !== false) {
                return true;
            }
        }
        
        // Vérifier les headers personnalisés que nous pouvons ajouter
        if ($request->header('X-Capacitor-App')) {
            return true;
        }
        
        // Vérifier le referer pour voir s'il contient le scheme de l'app
        $referer = $request->header('Referer', '');
        if (stripos($referer, 'capacitor://') !== false || 
            stripos($referer, 'fr.trouvetababysitter.mobile') !== false) {
            return true;
        }
        
        // ✅ AMÉLIORATION: Détecter si on vient d'un callback Google Auth mobile
        // En vérifiant la session ou des paramètres spécifiques
        if ($request->session()->has('google_mobile_auth')) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Détecte si la requête provient d'un appareil mobile (général)
     */
    public static function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        
        $mobileIndicators = [
            'Mobile',
            'Android',
            'iPhone',
            'iPad',
            'iOS'
        ];
        
        foreach ($mobileIndicators as $indicator) {
            if (stripos($userAgent, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Obtient l'URL de redirection appropriée pour l'environnement
     */
    public static function getRedirectUrl(Request $request, string $defaultUrl): string
    {
        if (self::isCapacitorApp($request)) {
            // Pour l'app mobile, rediriger vers la page de transition
            return '/mobile/callback';
        }
        
        return $defaultUrl;
    }
} 