import { toast } from 'vue-sonner';

export interface ApiResponse {
    status?: number;
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
}

export function useToast() {
    const showSuccess = (message: string, description?: string) => {
        toast.success(message, {
            description,
            duration: 4000,
        });
    };

    const showError = (message: string, description?: string) => {
        toast.error(message, {
            description,
            duration: 6000,
        });
    };

    const showWarning = (message: string, description?: string) => {
        toast.warning(message, {
            description,
            duration: 5000,
        });
    };

    const showInfo = (message: string, description?: string) => {
        toast.info(message, {
            description,
            duration: 4000,
        });
    };

    const handleApiResponse = (response: any, successMessage?: string) => {
        // Si c'est une réponse Inertia avec props
        if (response.props && response.props.flash) {
            const flash = response.props.flash;

            if (flash.success) {
                showSuccess('✨ Succès', flash.success);
                return;
            }

            if (flash.error) {
                showError('❌ Erreur', flash.error);
                return;
            }

            if (flash.warning) {
                showWarning('⚠️ Attention', flash.warning);
                return;
            }

            if (flash.info) {
                showInfo('ℹ️ Information', flash.info);
                return;
            }
        }

        // Gestion des status HTTP
        const status = response.status || response.response?.status;

        switch (status) {
            case 200:
            case 201:
                showSuccess('✨ ' + (successMessage || 'Opération réussie'), response.message || response.data?.message);
                break;

            case 422: // Erreurs de validation
                const errors = response.errors || response.response?.data?.errors;
                if (errors) {
                    const firstError = Object.values(errors)[0];
                    const errorMessage = Array.isArray(firstError) ? firstError[0] : firstError;
                    showError('❌ Erreur de validation', errorMessage as string);
                } else {
                    showError('❌ Erreur de validation', response.message || 'Les données saisies ne sont pas valides');
                }
                break;

            case 401:
                showError('🔒 Non autorisé', 'Vous devez vous connecter pour effectuer cette action');
                break;

            case 403:
                showError('🚫 Accès refusé', "Vous n'avez pas les permissions nécessaires");
                break;

            case 404:
                showError('🔍 Non trouvé', "La ressource demandée n'existe pas");
                break;

            case 500:
                // Vérifier si c'est une erreur de session expirée
                const errorMessage = response.message || response.error || '';
                if (errorMessage.includes('login not defined') || errorMessage.includes('Unauthenticated')) {
                    handleAuthError();
                } else {
                    showError('💥 Erreur serveur', "Une erreur inattendue s'est produite");
                }
                break;

            default:
                if (status >= 400) {
                    showError('❌ Erreur', response.message || response.error || 'Une erreur est survenue');
                } else if (successMessage) {
                    showSuccess('✨ ' + successMessage);
                }
        }
    };

    const handleApiError = (error: any) => {
        console.error('API Error:', error);

        if (error.response) {
            handleApiResponse(error.response);
        } else if (error.message) {
            showError('🌐 Erreur de connexion', error.message);
        } else {
            showError('❌ Erreur', "Une erreur inattendue s'est produite");
        }
    };

    const showVerificationRequired = () => {
        showWarning(
            '🔒 Vérification requise',
            'Votre profil doit être vérifié par notre équipe pour accéder à cette page. Rendez-vous dans votre profil pour demander la vérification.'
        );
    };

    const handleAuthError = () => {
        showError(
            '🔐 Session expirée',
            'Votre session a expiré. Vous allez être redirigé vers la page de connexion.'
        );
        
        // Redirection après 2 secondes
        setTimeout(() => {
            window.location.href = '/connexion';
        }, 2000);
    };

    return {
        showSuccess,
        showError,
        showWarning,
        showInfo,
        handleApiResponse,
        handleApiError,
        showVerificationRequired,
        handleAuthError,
    };
}
