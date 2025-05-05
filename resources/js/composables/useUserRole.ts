import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useUserRole() {
    const page = usePage();
    const user = computed(() => page.props.auth?.user ?? {});

    const role = computed(() => {
        if (!user.value || typeof user.value.role_id !== 'number') return 'unknown';
        if (user.value.role_id === 2) return 'parent';
        if (user.value.role_id === 3) return 'babysitter';
        return 'unknown';
    });

    const isParent = computed(() => role.value === 'parent');
    const isBabysitter = computed(() => role.value === 'babysitter');

    return { role, user, isParent, isBabysitter };
}
