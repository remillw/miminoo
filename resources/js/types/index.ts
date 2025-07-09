// Types de base
export interface User {
    id: number;
    firstname: string;
    lastname: string;
    email: string;
    phone?: string;
    avatar?: string;
    email_verified_at?: string;
    status: string;
    created_at: string;
    updated_at: string;
    date_of_birth?: string;
    slug?: string;
    roles?: Role[];
    address?: Address;
    parent_profile?: ParentProfile;
    babysitter_profile?: BabysitterProfile;
    // Propriétés pour la compatibilité avec certains composants
    name?: string; // Nom calculé (firstname + lastname)
    avatar_url?: string; // URL d'avatar alternative
    language?: string; // Langue de l'utilisateur
    google_id?: string;
    apple_id?: string;
    is_social_account?: boolean;
    password?: boolean;
    social_data_locked?: boolean;
    provider?: string;
    average_rating?: number;
    total_reviews?: number;
}

export interface Role {
    id: number;
    name: string;
    guard_name: string;
}

export interface Address {
    id: number;
    address: string;
    postal_code: string;
    country: string;
    latitude: number;
    longitude: number;
    google_place_id?: string;
}

export interface ParentProfile {
    id: number;
    user_id: number;
    children?: Child[];
    verification_status?: string;
}

export interface BabysitterProfile {
    id: number;
    user_id: number;
    bio?: string;
    experience_years?: number;
    available_radius_km?: number;
    availability?: any;
    hourly_rate?: number;
    documents_verified?: boolean;
    comfortable_with_all_ages?: boolean;
    languages?: Language[];
    skills?: Skill[];
    age_ranges?: AgeRange[];
    excluded_age_ranges?: AgeRange[];
    experiences?: any[];
    verification_status: 'pending' | 'verified' | 'rejected';
    rejection_reason?: string;
    is_available?: boolean;
    has_driving_license?: boolean;
    has_vehicle?: boolean;
    profile_photos?: string[];
    additional_photos_urls?: string[];
}

export interface Child {
    nom: string;
    age: string;
    unite: 'ans' | 'mois';
}

export interface Language {
    id: number;
    name: string;
    code?: string;
}

export interface Skill {
    id: number;
    name: string;
    description?: string;
    category?: string;
}

export interface AgeRange {
    id: number;
    name: string;
    min_age_months?: number;
    max_age_months?: number;
    display_order?: number;
}

// Types liés aux annonces
export interface Announcement {
    id: number;
    title: string;
    description?: string;
    date_start: string;
    date_end: string;
    hourly_rate: number;
    estimated_duration: number;
    estimated_total: number;
    status: string;
    created_at: string;
    updated_at: string;
    parent_id: number;
    address_id: number;
    children: Child[];
    additional_info?: string;
    distance?: number;
    parent: {
        id: number;
        firstname: string;
        lastname: string;
        avatar?: string;
        average_rating?: number;
        total_reviews?: number;
    };
    address: Address;
    applications?: Application[];
    applications_count?: number;
    slug?: string;
}

export interface Application {
    id: number;
    status: string;
    proposed_rate: number;
    counter_rate?: number;
    message?: string;
    created_at: string;
    updated_at: string;
    babysitter_id: number;
    ad_id: number;
    babysitter: {
        id: number;
        name: string;
        firstname: string;
        lastname: string;
        avatar?: string;
        average_rating?: number;
        total_reviews?: number;
    };
    ad?: {
        id: number;
        title: string;
        date_start: string;
        date_end: string;
    };
}

// Types liés aux réservations
export interface Reservation {
    id: number;
    status: string;
    hourly_rate: number;
    deposit_amount: number;
    service_fee: number;
    total_deposit: number;
    babysitter_amount: number;
    service_start_at: string;
    service_end_at: string;
    paid_at?: string;
    can_be_cancelled: boolean;
    can_be_reviewed: boolean;
    created_at: string;
    updated_at: string;
    parent_id: number;
    babysitter_id: number;
    ad_id: number;
    babysitter: {
        id: number;
        name: string;
        firstname: string;
        lastname: string;
        avatar?: string;
    };
    parent?: {
        id: number;
        name: string;
        firstname: string;
        lastname: string;
        avatar?: string;
    };
    ad: {
        id: number;
        title: string;
        date_start: string;
        date_end: string;
    };
}

// Types liés aux avis
export interface Review {
    id: number;
    rating: number;
    comment?: string;
    created_at: string;
    updated_at: string;
    reviewer_id: number;
    reviewed_id: number;
    reservation_id: number;
    role: 'parent' | 'babysitter';
    reviewer: {
        id: number;
        firstname: string;
        lastname: string;
        avatar?: string;
    };
    reviewer_name?: string;
}

// Types liés aux paiements
export interface Transaction {
    id: string;
    type: 'payment' | 'refund' | 'payout';
    amount: number;
    currency: string;
    status: string;
    created_at: string;
    created?: string; // Alternative pour created_at
    description?: string;
    reservation_id?: number;
    parent_name?: string;
    babysitter_name?: string;
    service_date?: string;
    service_start?: string; // Pour les données de service
    date?: string; // Date alternative
    duration?: number; // Durée du service en heures
    funds_status?: string;
    funds_message?: string;
    funds_release_date?: string;
    can_download_invoice?: boolean;
}

// Types pour les statistiques
export interface DashboardStats {
    active_ads?: number;
    bookings_this_month?: number;
    average_babysitter_rating?: number;
    hours_this_month?: number;
    earnings_this_month?: number;
    average_rating?: number;
    total_announcements?: number;
    active_announcements?: number;
    total_reservations?: number;
    completed_reservations?: number;
    total_spent?: number;
}

// Types pour la pagination
export interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    per_page: number;
    from?: number;
    to?: number;
    next_page_url?: string;
    prev_page_url?: string;
}

// Types pour les filtres
export interface Filters {
    search?: string;
    status?: string;
    announcement_status?: string;
    reservation_status?: string;
    application_status?: string;
    date_filter?: string;
    min_rate?: number;
    max_rate?: number;
    age_range?: string;
    date?: string;
    location?: string;
    latitude?: number;
    longitude?: number;
    type?: string; // Pour les filtres de paiements
}

// Types pour les notifications
export interface Notification {
    id: string;
    type: string;
    title: string;
    data?: any;
    read_at?: string;
    created_at: string;
}

// Types pour les modals
export interface ModalProps {
    show: boolean;
    title?: string;
    description?: string;
    type?: 'info' | 'warning' | 'danger' | 'success';
}

// Types pour les erreurs
export interface ValidationError {
    [key: string]: string[];
}

export interface ApiError {
    message: string;
    errors?: ValidationError;
}

// Types pour les composants et pages spécifiques
export interface NotificationSettings {
    email_notifications: boolean;
    push_notifications: boolean;
    sms_notifications: boolean;
}

export interface LanguageOption {
    code: string;
    name: string;
}

export interface StatsData {
    total_announcements?: number;
    active_announcements?: number;
    total_reservations?: number;
    completed_reservations?: number;
    total_spent?: number;
    pending_payments?: number;
}

// Types pour les modales et confirmations
export interface ConfirmationModal {
    show: boolean;
    title: string;
    message: string;
    type: 'warning' | 'danger' | 'info';
    confirmText?: string;
    cancelText?: string;
}

// Types pour les statuts étendus
export interface StatusOption {
    value: string;
    label: string;
}

// Export des types d'intersection et étendus
export type ExtendedUser = User & {
    parentProfile?: {
        children_ages: Child[];
    };
    role?: string;
};

export type UserWithRole = User & {
    role: string;
}; 