<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authentification réussie</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-align: center;
        }
        .container {
            max-width: 500px;
            padding: 2rem;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(255,255,255,0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .debug {
            font-size: 0.7rem;
            margin-top: 1rem;
            opacity: 0.9;
            text-align: left;
            background: rgba(0,0,0,0.3);
            padding: 1rem;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
        }
        .button-group {
            margin: 1rem 0;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        .test-button {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        .test-button:hover {
            background: rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>🔐 Authentification réussie !</h1>
        <p>Tentative de retour vers l'application mobile...</p>
        
        <div class="button-group">
            <button class="test-button" onclick="testScheme1()">🧪 Test Scheme 1</button>
            <button class="test-button" onclick="testScheme2()">🧪 Test Scheme 2</button>
            <button class="test-button" onclick="testScheme3()">🧪 Test Scheme 3</button>
            <button class="test-button" onclick="goToDashboard()">🏠 Web Dashboard</button>
        </div>
        
        <div class="debug" id="debug-info"></div>
    </div>

    <script>
        console.log('🔄 Page callback mobile chargée');
        
        const debugEl = document.getElementById('debug-info');
        let debugSteps = [];
        
        function addDebug(step) {
            const timestamp = new Date().toLocaleTimeString();
            const message = `${timestamp}: ${step}`;
            debugSteps.push(message);
            
            // Garder seulement les 20 derniers messages
            if (debugSteps.length > 20) {
                debugSteps = debugSteps.slice(-20);
            }
            
            debugEl.innerHTML = debugSteps.join('<br>');
            console.log(message);
        }
        
        addDebug('🔄 Début du processus de redirection mobile');
        addDebug('🌐 User Agent: ' + navigator.userAgent.substring(0, 50) + '...');
        addDebug('📍 URL actuelle: ' + window.location.href);
        addDebug('🕒 Timestamp: ' + Date.now());
        
        // Détecter l'environnement
        const isCapacitor = window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform();
        addDebug('📱 Environnement Capacitor: ' + (isCapacitor ? 'OUI' : 'NON'));
        
        if (window.Capacitor) {
            addDebug('🔧 Capacitor détecté, platform: ' + (window.Capacitor.getPlatform ? window.Capacitor.getPlatform() : 'unknown'));
        }
        
        // Tests manuels de schemes
        function testScheme1() {
            const scheme = 'trouvetababysitter://auth/callback?success=1&test=1&t=' + Date.now();
            addDebug('🧪 Test Scheme 1: ' + scheme);
            try {
                window.location.href = scheme;
                addDebug('✅ Scheme 1 lancé');
            } catch (error) {
                addDebug('❌ Erreur Scheme 1: ' + error.message);
            }
        }
        
        function testScheme2() {
            const scheme = 'fr.trouvetababysitter.mobile://auth/callback?success=1&test=2&t=' + Date.now();
            addDebug('🧪 Test Scheme 2: ' + scheme);
            try {
                window.location.href = scheme;
                addDebug('✅ Scheme 2 lancé');
            } catch (error) {
                addDebug('❌ Erreur Scheme 2: ' + error.message);
            }
        }
        
        function testScheme3() {
            const scheme = 'capacitor://trouvetababysitter?path=/auth/callback&success=1&test=3&t=' + Date.now();
            addDebug('🧪 Test Scheme 3: ' + scheme);
            try {
                window.location.href = scheme;
                addDebug('✅ Scheme 3 lancé');
            } catch (error) {
                addDebug('❌ Erreur Scheme 3: ' + error.message);
            }
        }
        
        // Fonction pour aller au tableau de bord web
        function goToDashboard() {
            addDebug('🏠 Redirection vers tableau de bord web...');
            window.location.href = '/tableau-de-bord?mobile_auth=success&register_device_token=1';
        }
        
        // Fonction principale de redirection automatique
        function attemptAppRedirect() {
            addDebug('📱 Tentative de redirection automatique vers l\'app...');
            
            // Essayer le scheme principal
            const customScheme = 'trouvetababysitter://auth/callback?success=1&auto=1&timestamp=' + Date.now();
            
            addDebug('🔗 Tentative automatique avec: ' + customScheme);
            
            try {
                // Créer un événement de redirection
                window.location.href = customScheme;
                addDebug('✅ window.location.href exécuté pour redirection automatique');
                
                // Marquer qu'on a tenté la redirection
                sessionStorage.setItem('redirect_attempted', 'true');
                
            } catch (error) {
                addDebug('❌ Erreur redirection automatique: ' + error.message);
            }
        }
        
        // Redirection automatique après 3 secondes
        setTimeout(() => {
            addDebug('⏰ Déclenchement redirection automatique...');
            attemptAppRedirect();
        }, 3000);
        
        // Fallback vers le tableau de bord web après 10 secondes
        setTimeout(() => {
            if (!sessionStorage.getItem('page_left')) {
                addDebug('⚠️ Timeout 10s atteint, redirection vers le web...');
                goToDashboard();
            }
        }, 10000);
        
        // Détection si l'utilisateur quitte la page
        let pageLeft = false;
        
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden' && !pageLeft) {
                pageLeft = true;
                sessionStorage.setItem('page_left', 'true');
                addDebug('✅ Page cachée - App probablement ouverte !');
            } else if (document.visibilityState === 'visible' && pageLeft) {
                addDebug('⚠️ Retour sur la page - Redirection app a échoué');
            }
        });
        
        // Détection de focus/blur
        window.addEventListener('blur', () => {
            if (!pageLeft) {
                addDebug('👁️ Page perdue le focus');
            }
        });
        
        window.addEventListener('focus', () => {
            if (pageLeft) {
                addDebug('🔄 Page récupérée le focus - retour inattendu');
            }
        });
        
        // Log de performance
        addDebug('⚡ Page callback chargée en ' + Math.round(performance.now()) + 'ms');
    </script>
</body>
</html> 