export interface StatusColorConfig {
    text: string;
    badge: string;
    background: string;
}

export function useStatusColors() {
    // Couleurs uniformisées pour tous les statuts de réservation
    const reservationStatusColors: Record<string, StatusColorConfig> = {
        'pending_payment': {
            text: 'text-yellow-800',
            badge: 'bg-yellow-100 text-yellow-800',
            background: 'bg-yellow-50 border-yellow-200'
        },
        'paid': {
            text: 'text-blue-800',
            badge: 'bg-blue-100 text-blue-800',
            background: 'bg-blue-50 border-blue-200'
        },
        'active': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        },
        'service_completed': {
            text: 'text-purple-800',
            badge: 'bg-purple-100 text-purple-800',
            background: 'bg-purple-50 border-purple-200'
        },
        'completed': {
            text: 'text-gray-800',
            badge: 'bg-gray-100 text-gray-800',
            background: 'bg-gray-50 border-gray-200'
        },
        'cancelled_by_parent': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'cancelled_by_babysitter': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'cancelled': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'refunded': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        }
    };

    // Couleurs pour les statuts d'annonces
    const announcementStatusColors: Record<string, StatusColorConfig> = {
        'active': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        },
        'paused': {
            text: 'text-yellow-800',
            badge: 'bg-yellow-100 text-yellow-800',
            background: 'bg-yellow-50 border-yellow-200'
        },
        'booked': {
            text: 'text-blue-800',
            badge: 'bg-blue-100 text-blue-800',
            background: 'bg-blue-50 border-blue-200'
        },
        'completed': {
            text: 'text-gray-800',
            badge: 'bg-gray-100 text-gray-800',
            background: 'bg-gray-50 border-gray-200'
        },
        'cancelled': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'expired': {
            text: 'text-gray-800',
            badge: 'bg-gray-100 text-gray-800',
            background: 'bg-gray-50 border-gray-200'
        }
    };

    // Couleurs pour les statuts de candidatures
    const applicationStatusColors: Record<string, StatusColorConfig> = {
        'pending': {
            text: 'text-yellow-800',
            badge: 'bg-yellow-100 text-yellow-800',
            background: 'bg-yellow-50 border-yellow-200'
        },
        'counter_offered': {
            text: 'text-blue-800',
            badge: 'bg-blue-100 text-blue-800',
            background: 'bg-blue-50 border-blue-200'
        },
        'accepted': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        },
        'declined': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'cancelled': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'archived': {
            text: 'text-gray-800',
            badge: 'bg-gray-100 text-gray-800',
            background: 'bg-gray-50 border-gray-200'
        }
    };

    // Couleurs pour les statuts de fonds
    const fundsStatusColors: Record<string, StatusColorConfig> = {
        'pending_service': {
            text: 'text-yellow-800',
            badge: 'bg-yellow-100 text-yellow-800',
            background: 'bg-yellow-50 border-yellow-200'
        },
        'held_for_validation': {
            text: 'text-blue-800',
            badge: 'bg-blue-100 text-blue-800',
            background: 'bg-blue-50 border-blue-200'
        },
        'released': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        },
        'disputed': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        }
    };

    // Couleurs pour les statuts de compte Stripe
    const stripeAccountStatusColors: Record<string, StatusColorConfig> = {
        'pending': {
            text: 'text-yellow-800',
            badge: 'bg-yellow-100 text-yellow-800',
            background: 'bg-yellow-50 border-yellow-200'
        },
        'active': {
            text: 'text-green-800',
            badge: 'bg-green-100 text-green-800',
            background: 'bg-green-50 border-green-200'
        },
        'restricted': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'rejected': {
            text: 'text-red-800',
            badge: 'bg-red-100 text-red-800',
            background: 'bg-red-50 border-red-200'
        },
        'inactive': {
            text: 'text-gray-800',
            badge: 'bg-gray-100 text-gray-800',
            background: 'bg-gray-50 border-gray-200'
        }
    };

    // Textes français pour les statuts
    const statusTexts: Record<string, Record<string, string>> = {
        reservation: {
            'pending_payment': 'En attente de paiement',
            'paid': 'Payé',
            'active': 'En cours',
            'service_completed': 'Service terminé',
            'completed': 'Terminé',
            'cancelled_by_parent': 'Annulé par le parent',
            'cancelled_by_babysitter': 'Annulé par la babysitter',
            'cancelled': 'Annulé',
            'refunded': 'Remboursé'
        },
        announcement: {
            'active': 'Active',
            'paused': 'En pause',
            'booked': 'Réservée',
            'completed': 'Terminée',
            'cancelled': 'Annulée',
            'expired': 'Expirée'
        },
        application: {
            'pending': 'En attente',
            'counter_offered': 'Contre-offre',
            'accepted': 'Acceptée',
            'declined': 'Refusée',
            'cancelled': 'Annulée',
            'archived': 'Archivée'
        },
        funds: {
            'pending_service': 'En attente du service',
            'held_for_validation': 'En attente de libération',
            'released': 'Fonds libérés',
            'disputed': 'Litige en cours'
        },
        stripeAccount: {
            'pending': 'En attente',
            'active': 'Actif',
            'restricted': 'Restreint',
            'rejected': 'Rejeté',
            'inactive': 'Inactif'
        }
    };

    // Fonctions helper
    const getReservationStatusColor = (status: string): StatusColorConfig => {
        return reservationStatusColors[status] || reservationStatusColors['completed'];
    };

    const getAnnouncementStatusColor = (status: string): StatusColorConfig => {
        return announcementStatusColors[status] || announcementStatusColors['active'];
    };

    const getApplicationStatusColor = (status: string): StatusColorConfig => {
        return applicationStatusColors[status] || applicationStatusColors['pending'];
    };

    const getFundsStatusColor = (status: string): StatusColorConfig => {
        return fundsStatusColors[status] || fundsStatusColors['pending_service'];
    };

    const getStripeAccountStatusColor = (status: string): StatusColorConfig => {
        return stripeAccountStatusColors[status] || stripeAccountStatusColors['pending'];
    };

    const getStatusText = (type: string, status: string): string => {
        return statusTexts[type]?.[status] || status;
    };

    return {
        // Fonctions pour obtenir les couleurs
        getReservationStatusColor,
        getAnnouncementStatusColor,
        getApplicationStatusColor,
        getFundsStatusColor,
        getStripeAccountStatusColor,
        
        // Fonction pour obtenir le texte
        getStatusText,
        
        // Export des objets de configuration complets si nécessaire
        reservationStatusColors,
        announcementStatusColors,
        applicationStatusColors,
        fundsStatusColors,
        stripeAccountStatusColors,
        statusTexts
    };
} 