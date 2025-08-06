// Script à injecter dans la WebView Expo pour marquer qu'on est dans l'app
(function () {
    // Marquer qu'on est dans l'app Expo
    window.isExpoApp = true;
    window.__EXPO_WEBVIEW__ = true;

    // Logger pour debug
    console.log('[Expo Injection] App mobile détectée');

    // Forcer le recalcul des conditions de l'app si les composants sont déjà montés
    if (window.dispatchEvent) {
        window.dispatchEvent(new Event('expo-app-loaded'));
    }
})();
