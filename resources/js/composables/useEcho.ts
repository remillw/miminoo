import { onMounted, ref } from 'vue';
import { isEchoReady, waitForEcho } from '../echo';

export function useEcho() {
    const echoReady = ref(false);
    const echo = ref<any>(null);

    // Attendre que Echo soit prêt
    const initializeEcho = async () => {
        try {
            echo.value = await waitForEcho();
            echoReady.value = !!echo.value;
            console.log('🎯 Echo prêt dans le composable:', echoReady.value);
        } catch (error) {
            console.error('❌ Erreur initialisation Echo dans composable:', error);
            echoReady.value = false;
        }
    };

    // Fonction pour écouter un canal
    const listenToChannel = (channelName: string, eventName: string, callback: (data: any) => void) => {
        if (!echo.value) {
            console.warn('⚠️ Echo pas encore prêt pour écouter le canal:', channelName);
            return null;
        }

        console.log('👂 Écoute du canal:', channelName, 'événement:', eventName);
        const channel = echo.value.private(channelName);
        channel.listen(eventName, callback);
        return channel;
    };

    // Fonction pour écouter un canal de présence
    const listenToPresenceChannel = (channelName: string) => {
        if (!echo.value) {
            console.warn('⚠️ Echo pas encore prêt pour le canal de présence:', channelName);
            return null;
        }

        console.log('👥 Connexion au canal de présence:', channelName);
        return echo.value.join(channelName);
    };

    // Fonction pour quitter un canal
    const leaveChannel = (channelName: string) => {
        if (!echo.value) return;

        console.log('👋 Quitter le canal:', channelName);
        echo.value.leave(channelName);
    };

    onMounted(() => {
        // Vérifier si Echo est déjà prêt
        if (isEchoReady()) {
            echo.value = window.Echo;
            echoReady.value = true;
            console.log('🎯 Echo déjà disponible dans window.Echo');
        } else {
            // Sinon attendre qu'il soit prêt
            initializeEcho();
        }
    });

    return {
        echoReady,
        echo,
        listenToChannel,
        listenToPresenceChannel,
        leaveChannel,
        initializeEcho,
    };
}
