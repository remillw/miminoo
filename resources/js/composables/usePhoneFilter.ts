import { useToast } from '@/composables/useToast';

export function usePhoneFilter() {
    const { showError } = useToast();

    // Expressions régulières pour détecter les numéros de téléphone français et internationaux
    const phonePatterns = [
        // Numéros français classiques
        /(\b0[1-9])[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})\b/g,
        // Format international +33
        /(\+33[\s\-\.]?[1-9])[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})[\s\-\.]?([0-9]{2})\b/g,
        // Format avec parenthèses
        /(\(?\d{2,3}\)?[\s\-\.]?){2,5}\b/g,
        // Numéros sans espaces (8 chiffres minimum)
        /\b\d{8,15}\b/g,
        // Format international générique
        /\+\d{1,3}[\s\-\.]?\d{6,14}\b/g,
    ];

    /**
     * Vérifie si un texte contient un numéro de téléphone
     */
    const containsPhoneNumber = (text: string): boolean => {
        // Ignorer les codes postaux (exactement 5 chiffres)
        const postalCodePattern = /\b\d{5}\b/g;
        const withoutPostalCodes = text.replace(postalCodePattern, '');
        
        // Ignorer les dates et heures
        const dateTimePattern = /\b\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}\b|\b\d{1,2}:\d{2}\b|\b\d{4}\b/g;
        const withoutDateTime = withoutPostalCodes.replace(dateTimePattern, '');
        
        return phonePatterns.some(pattern => {
            pattern.lastIndex = 0; // Reset regex
            const matches = withoutDateTime.match(pattern);
            if (matches) {
                // Vérifier que ce n'est pas juste une séquence de chiffres courte
                return matches.some(match => {
                    const digitsOnly = match.replace(/[\s\-\.\(\)\+]/g, '');
                    return digitsOnly.length >= 8; // Au moins 8 chiffres pour un numéro valide
                });
            }
            return false;
        });
    };

    /**
     * Remplace tous les numéros de téléphone par des astérisques
     */
    const maskPhoneNumbers = (text: string): string => {
        let maskedText = text;
        
        // Ignorer les codes postaux (exactement 5 chiffres)
        const postalCodePattern = /\b\d{5}\b/g;
        const postalCodes: string[] = [];
        maskedText = maskedText.replace(postalCodePattern, (match) => {
            const placeholder = `__POSTAL_${postalCodes.length}__`;
            postalCodes.push(match);
            return placeholder;
        });
        
        // Ignorer les dates et heures
        const dateTimePattern = /\b\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}\b|\b\d{1,2}:\d{2}\b|\b\d{4}\b/g;
        const dateTimes: string[] = [];
        maskedText = maskedText.replace(dateTimePattern, (match) => {
            const placeholder = `__DATETIME_${dateTimes.length}__`;
            dateTimes.push(match);
            return placeholder;
        });

        // Remplacer les numéros de téléphone par des astérisques
        phonePatterns.forEach(pattern => {
            pattern.lastIndex = 0; // Reset regex
            maskedText = maskedText.replace(pattern, (match) => {
                const digitsOnly = match.replace(/[\s\-\.\(\)\+]/g, '');
                if (digitsOnly.length >= 8) {
                    return '*'.repeat(match.length);
                }
                return match; // Garder si trop court
            });
        });

        // Restaurer les codes postaux et dates
        postalCodes.forEach((code, index) => {
            maskedText = maskedText.replace(`__POSTAL_${index}__`, code);
        });
        dateTimes.forEach((dateTime, index) => {
            maskedText = maskedText.replace(`__DATETIME_${index}__`, dateTime);
        });

        return maskedText;
    };

    /**
     * Vérifie et filtre un message avant l'envoi
     * Retourne false si le message contient un numéro et affiche un toast
     */
    const filterMessage = (message: string): { isAllowed: boolean; filteredMessage: string } => {
        if (containsPhoneNumber(message)) {
            showError(
                'Numéro interdit',
                'Les numéros de téléphone dans le chat sont interdits. Vous n\'aurez accès au numéro qu\'après avoir réservé.'
            );
            
            return {
                isAllowed: false,
                filteredMessage: maskPhoneNumbers(message)
            };
        }

        return {
            isAllowed: true,
            filteredMessage: message
        };
    };

    return {
        containsPhoneNumber,
        maskPhoneNumbers,
        filterMessage
    };
}