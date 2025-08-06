import { PageProps as InertiaPageProps } from '@inertiajs/core';
import Echo from 'laravel-echo';
import { route as ziggyRoute } from 'ziggy-js';

declare global {
    interface Window {
        io: any;
        Echo: Echo;
    }

    var route: typeof ziggyRoute;
}

export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    avatar?: string;
    role?: string;
    created_at: string;
    updated_at: string;
}

export interface PageProps extends InertiaPageProps {
    auth: {
        user: User;
    };
    ziggy: {
        location: string;
        query?: Record<string, string>;
    };
}

export interface Message {
    id: number;
    conversation_id: number;
    sender_id: number | null;
    message: string;
    type: 'user' | 'system';
    system_data?: any;
    read_at?: string;
    created_at: string;
    updated_at: string;
    sender?: User;
}
