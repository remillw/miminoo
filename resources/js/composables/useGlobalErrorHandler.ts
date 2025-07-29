import { useToast } from './useToast';

export function useGlobalErrorHandler() {
    const { handleAuthError } = useToast();

    // Fonction pour vérifier si c'est une erreur de session expirée
    const isSessionExpiredError = (error: any): boolean => {
        // Vérifier différents formats d'erreurs
        const errorMessage = 
            error?.message || 
            error?.data?.message || 
            error?.response?.data?.message || 
            error?.responseText || 
            JSON.stringify(error) || 
            '';

        return errorMessage.toLowerCase().includes('login not defined') ||
               errorMessage.toLowerCase().includes('unauthenticated') ||
               errorMessage.toLowerCase().includes('session expired') ||
               (error?.status === 500 && errorMessage.includes('undefined'));
    };

    // Intercepter les requêtes fetch globalement
    const originalFetch = window.fetch;
    window.fetch = async (...args) => {
        try {
            const response = await originalFetch.apply(window, args);
            
            // Vérifier les erreurs 500
            if (response.status === 500) {
                try {
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        const errorData = await response.clone().json();
                        if (isSessionExpiredError(errorData)) {
                            handleAuthError();
                            return response;
                        }
                    } else {
                        // Pour les réponses non-JSON, vérifier le texte
                        const errorText = await response.clone().text();
                        if (isSessionExpiredError({ message: errorText })) {
                            handleAuthError();
                            return response;
                        }
                    }
                } catch (e) {
                    // Si on ne peut pas parser la réponse, ignorer
                    console.warn('Impossible de parser la réponse d\'erreur 500:', e);
                }
            }
            
            return response;
        } catch (error) {
            // Vérifier les erreurs de réseau
            if (isSessionExpiredError(error)) {
                handleAuthError();
            }
            throw error;
        }
    };

    // Gestionnaire global pour les erreurs non capturées
    const handleGlobalError = (event: ErrorEvent) => {
        if (isSessionExpiredError({ message: event.message })) {
            handleAuthError();
        }
    };

    // Gestionnaire pour les promesses rejetées non capturées
    const handleUnhandledRejection = (event: PromiseRejectionEvent) => {
        if (isSessionExpiredError(event.reason)) {
            handleAuthError();
        }
    };

    // Installer les gestionnaires globaux
    const installGlobalHandlers = () => {
        window.addEventListener('error', handleGlobalError);
        window.addEventListener('unhandledrejection', handleUnhandledRejection);
    };

    // Désinstaller les gestionnaires globaux
    const uninstallGlobalHandlers = () => {
        window.removeEventListener('error', handleGlobalError);
        window.removeEventListener('unhandledrejection', handleUnhandledRejection);
    };

    return {
        isSessionExpiredError,
        installGlobalHandlers,
        uninstallGlobalHandlers
    };
}