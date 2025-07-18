import { route } from 'ziggy-js';

/**
 * Composable pour la gestion centralisée des routes avec Ziggy
 */
export function useRoutes() {
    // Routes d'authentification
    const authRoutes = {
        login: () => '/connexion',
        register: () => '/inscription',
        logout: () => '/deconnexion',
        passwordRequest: () => '/forgot-password',
        passwordEmail: () => '/password/email',
        passwordStore: () => '/password/store',
        passwordConfirm: () => '/password/confirm',
        verificationSend: () => '/email/verification-notification',
        home: () => '/',
    };

    // Routes principales de l'application
    const appRoutes = {
        dashboard: () => '/tableau-de-bord',
        profile: () => '/profil',
    };

    

    // Routes pour les paramètres
    const settingsRoutes = {
        profile: () => '/profil',
        update: () => '/profil',
        destroy: () => '/profil',
        security: () => '/password/update',
        appearance: () => '/appearance',
    };

    // Fonction utilitaire pour vérifier si une route existe
    const routeExists = (routeName: string): boolean => {
        try {
            route(routeName);
            return true;
        } catch {
            return false;
        }
    };

    // Fonction pour obtenir l'URL actuelle
    const currentRoute = () => window.location.pathname;

    // Fonction pour vérifier si on est sur une route spécifique
    const isCurrentRoute = (routeName: string, params?: any): boolean => {
        try {
            return route(routeName, params, false) === currentRoute();
        } catch {
            return false;
        }
    };

    return {
        // Groupes de routes
        authRoutes,
        appRoutes,
        settingsRoutes,
        
        // Fonctions utilitaires
        route,
        routeExists,
        currentRoute,
        isCurrentRoute,
    };
} 