import { ref, watch } from 'vue';

type UserMode = 'parent' | 'babysitter';

const STORAGE_KEY = 'miminoo_user_mode';

// État global réactif
const currentMode = ref<UserMode>('parent');

export function useUserMode() {
    // Initialiser depuis localStorage au premier chargement
    const initializeMode = (hasParentRole: boolean, hasBabysitterRole: boolean, serverMode?: UserMode) => {
        let mode: UserMode;
        
        // Priorité 1: mode du serveur (paramètre URL)
        if (serverMode) {
            mode = serverMode;
        }
        // Priorité 2: localStorage
        else {
            const stored = localStorage.getItem(STORAGE_KEY) as UserMode | null;
            if (stored && (stored === 'parent' || stored === 'babysitter')) {
                // Vérifier que l'utilisateur a bien ce rôle
                if ((stored === 'parent' && hasParentRole) || (stored === 'babysitter' && hasBabysitterRole)) {
                    mode = stored;
                } else {
                    // Fallback si le rôle stocké n'est pas valide
                    mode = hasParentRole ? 'parent' : 'babysitter';
                }
            } else {
                // Priorité 3: par défaut parent s'il l'a, sinon babysitter
                mode = hasParentRole ? 'parent' : 'babysitter';
            }
        }
        
        currentMode.value = mode;
        localStorage.setItem(STORAGE_KEY, mode);
        return mode;
    };

    // Changer de mode
    const setMode = (mode: UserMode) => {
        currentMode.value = mode;
        localStorage.setItem(STORAGE_KEY, mode);
    };

    // Obtenir le mode actuel
    const getMode = () => currentMode.value;

    // Watcher pour synchroniser avec localStorage
    watch(currentMode, (newMode) => {
        localStorage.setItem(STORAGE_KEY, newMode);
    });

    return {
        currentMode,
        initializeMode,
        setMode,
        getMode,
    };
} 