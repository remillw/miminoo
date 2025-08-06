import { ref } from 'vue';

type UserMode = 'parent' | 'babysitter';

const STORAGE_KEY = 'babysitter_user_mode';

// État global réactif
const currentMode = ref<UserMode>('parent');

export function useUserMode() {
    // Initialiser depuis localStorage au premier chargement
    const initializeMode = (hasParentRole: boolean, hasBabysitterRole: boolean, serverMode?: UserMode) => {
        let mode: UserMode;

        console.log('🔧 Initialisation mode:', { hasParentRole, hasBabysitterRole, serverMode });

        // PRIORITÉ 1: localStorage (pour respecter le choix de l'utilisateur)
        const stored = localStorage.getItem(STORAGE_KEY) as UserMode | null;
        console.log('🔍 Mode localStorage:', stored);

        if (stored && (stored === 'parent' || stored === 'babysitter')) {
            // Vérifier que l'utilisateur a bien ce rôle
            if ((stored === 'parent' && hasParentRole) || (stored === 'babysitter' && hasBabysitterRole)) {
                mode = stored;
                console.log('✅ Mode localStorage valide utilisé:', mode);
            } else {
                // Fallback si le rôle stocké n'est pas valide
                mode = hasParentRole ? 'parent' : 'babysitter';
                console.log('⚠️ Mode localStorage invalide, fallback:', mode);
            }
        }
        // PRIORITÉ 2: Mode serveur si pas de localStorage
        else if (serverMode && ((serverMode === 'parent' && hasParentRole) || (serverMode === 'babysitter' && hasBabysitterRole))) {
            mode = serverMode;
            console.log('✅ Mode serveur utilisé (pas de localStorage):', mode);
        }
        // PRIORITÉ 3: Mode par défaut
        else {
            mode = hasParentRole ? 'parent' : 'babysitter';
            console.log('🆕 Mode par défaut:', mode);
        }

        currentMode.value = mode;
        localStorage.setItem(STORAGE_KEY, mode);
        console.log('💾 Mode final sauvegardé:', mode);
        return mode;
    };

    // Changer de mode
    const setMode = (mode: UserMode) => {
        currentMode.value = mode;
        localStorage.setItem(STORAGE_KEY, mode);
    };

    // Obtenir le mode actuel
    const getMode = () => currentMode.value;

    // Pas de watcher automatique pour éviter les boucles infinies
    // Le localStorage est mis à jour explicitement dans setMode() et initializeMode()

    return {
        currentMode,
        initializeMode,
        setMode,
        getMode,
    };
}
