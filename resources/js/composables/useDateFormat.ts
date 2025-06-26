export function useDateFormat() {
    /**
     * Parse une date en tenant compte du timezone local pour éviter les décalages
     */
    const parseLocalDate = (dateString: string): Date => {
        // Si la date contient 'Z' ou '+', c'est une date UTC, on la traite comme locale
        if (dateString.includes('Z') || dateString.includes('+')) {
            return new Date(dateString.replace(/Z.*$/, ''));
        }
        return new Date(dateString);
    };

    /**
     * Formate une date pour l'affichage français
     */
    const formatDate = (date: Date): string => {
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: 'long',
            year: 'numeric',
        });
    };

    /**
     * Formate une heure pour l'affichage français
     */
    const formatTime = (date: Date): string => {
        return date.toLocaleTimeString('fr-FR', {
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    /**
     * Formate une date complète avec heure
     */
    const formatDateTime = (date: Date): string => {
        return date.toLocaleDateString('fr-FR', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    /**
     * Vérifie si deux dates sont sur des jours différents
     */
    const isMultiDay = (startDate: Date, endDate: Date): boolean => {
        return startDate.toDateString() !== endDate.toDateString();
    };

    /**
     * Calcule la différence en jours entre deux dates
     */
    const daysDifference = (startDate: Date, endDate: Date): number => {
        return Math.ceil((endDate.getTime() - startDate.getTime()) / (1000 * 60 * 60 * 24));
    };

    /**
     * Formate une plage de dates (avec gestion multi-jours)
     */
    const formatDateRange = (
        startDateString: string,
        endDateString: string,
        estimatedDuration?: number,
    ): {
        dateDisplay: string;
        timeDisplay: string;
        isMultiDay: boolean;
    } => {
        const dateStart = parseLocalDate(startDateString);
        const dateEnd = parseLocalDate(endDateString);

        const isMultiDayValue = isMultiDay(dateStart, dateEnd);
        const daysDiff = daysDifference(dateStart, dateEnd);

        let dateDisplay, timeDisplay;

        if (isMultiDayValue && daysDiff > 0) {
            dateDisplay = `Du ${formatDate(dateStart)} au ${formatDate(dateEnd)}`;
            timeDisplay = `${formatTime(dateStart)} - ${formatTime(dateEnd)}`;
        } else {
            dateDisplay = formatDate(dateStart);
            timeDisplay = `${formatTime(dateStart)} - ${formatTime(dateEnd)}`;
        }

        return {
            dateDisplay,
            timeDisplay,
            isMultiDay: isMultiDayValue,
        };
    };

    /**
     * Formate "il y a X temps"
     */
    const formatTimeAgo = (dateString: string): string => {
        const now = new Date();
        const date = parseLocalDate(dateString);
        const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));

        if (diffInMinutes < 1) return "À l'instant";
        if (diffInMinutes < 60) return `Il y a ${diffInMinutes} min`;

        const diffInHours = Math.floor(diffInMinutes / 60);
        if (diffInHours < 24) return `Il y a ${diffInHours}h`;

        const diffInDays = Math.floor(diffInHours / 24);
        if (diffInDays < 7) return `Il y a ${diffInDays} jour${diffInDays > 1 ? 's' : ''}`;

        const diffInWeeks = Math.floor(diffInDays / 7);
        if (diffInWeeks < 4) return `Il y a ${diffInWeeks} semaine${diffInWeeks > 1 ? 's' : ''}`;

        const diffInMonths = Math.floor(diffInDays / 30);
        return `Il y a ${diffInMonths} mois`;
    };

    return {
        parseLocalDate,
        formatDate,
        formatTime,
        formatDateTime,
        formatDateRange,
        formatTimeAgo,
        isMultiDay,
        daysDifference,
    };
}
