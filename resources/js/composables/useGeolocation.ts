import { onMounted, ref } from 'vue';

interface Position {
    latitude: number;
    longitude: number;
}

const userPosition = ref<Position | null>(null);
const isGeolocationEnabled = ref(false);
const isLoading = ref(false);
const error = ref<string | null>(null);

export function useGeolocation() {
    const requestGeolocation = async (): Promise<Position | null> => {
        if (!navigator.geolocation) {
            error.value = "La géolocalisation n'est pas supportée par ce navigateur";
            return null;
        }

        isLoading.value = true;
        error.value = null;

        try {
            const position = await new Promise<GeolocationPosition>((resolve, reject) => {
                navigator.geolocation.getCurrentPosition(resolve, reject, {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000, // 5 minutes
                });
            });

            const coords = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
            };

            userPosition.value = coords;
            isGeolocationEnabled.value = true;

            // Sauvegarder en localStorage
            localStorage.setItem('user_position', JSON.stringify(coords));
            localStorage.setItem('geolocation_enabled', 'true');

            return coords;
        } catch (err: any) {
            let errorMessage = "Impossible d'obtenir votre position";

            if (err.code === 1) {
                errorMessage = 'Accès à la géolocalisation refusé';
            } else if (err.code === 2) {
                errorMessage = 'Position indisponible';
            } else if (err.code === 3) {
                errorMessage = "Délai d'attente dépassé";
            }

            error.value = errorMessage;
            isGeolocationEnabled.value = false;
            return null;
        } finally {
            isLoading.value = false;
        }
    };

    const calculateDistance = (lat1: number, lon1: number, lat2: number, lon2: number): number => {
        const R = 6371; // Rayon de la Terre en kilomètres
        const dLat = ((lat2 - lat1) * Math.PI) / 180;
        const dLon = ((lon2 - lon1) * Math.PI) / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos((lat1 * Math.PI) / 180) * Math.cos((lat2 * Math.PI) / 180) * Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const d = R * c; // Distance en kilomètres
        return Math.round(d * 10) / 10; // Arrondir à 1 décimale
    };

    const getDistanceFromUser = (latitude: number, longitude: number): number | null => {
        if (!userPosition.value) return null;
        return calculateDistance(userPosition.value.latitude, userPosition.value.longitude, latitude, longitude);
    };

    const loadSavedPosition = () => {
        const saved = localStorage.getItem('user_position');
        const enabled = localStorage.getItem('geolocation_enabled');

        if (saved && enabled === 'true') {
            try {
                userPosition.value = JSON.parse(saved);
                isGeolocationEnabled.value = true;
            } catch (e) {
                console.error('Erreur lors du chargement de la position sauvegardée:', e);
            }
        }
    };

    const disableGeolocation = () => {
        userPosition.value = null;
        isGeolocationEnabled.value = false;
        localStorage.removeItem('user_position');
        localStorage.removeItem('geolocation_enabled');
    };

    // Charger la position sauvegardée au montage
    onMounted(() => {
        loadSavedPosition();
    });

    return {
        userPosition,
        isGeolocationEnabled,
        isLoading,
        error,
        requestGeolocation,
        getDistanceFromUser,
        disableGeolocation,
        loadSavedPosition,
    };
}
