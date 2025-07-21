<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification réussie</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            text-align: center;
            max-width: 400px;
            padding: 2rem;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        h1 {
            margin: 0 0 1rem 0;
            font-size: 1.5rem;
            font-weight: 600;
        }
        p {
            margin: 0 0 1.5rem 0;
            opacity: 0.9;
            font-size: 1rem;
        }
        .debug-info {
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.8rem;
            text-align: left;
            max-height: 200px;
            overflow-y: auto;
        }
        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
            margin-top: 1rem;
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
        <p>Retour vers l'application mobile en cours...</p>
        
        <div class="button-group">
            <button class="test-button" onclick="testScheme()">🧪 Test Scheme</button>
            <button class="test-button" onclick="goToDashboard()">🏠 Web Dashboard</button>
        </div>
        
        <div id="debug-info" class="debug-info"></div>
    </div>

    <script>
        console.log('🔄 Page callback mobile chargée');
        const debugEl = document.getElementById('debug-info');
        let debugSteps = [];

        function addDebug(step) {
            console.log(step);
            debugSteps.push(`${new Date().toLocaleTimeString()}: ${step}`);
            if (debugEl) {
                debugEl.innerHTML = debugSteps.slice(-10).join('<br>');
                debugEl.scrollTop = debugEl.scrollHeight;
            }
        }

        addDebug('🔄 Début du processus de redirection mobile');
        addDebug('📱 User Agent: ' + navigator.userAgent.substring(0, 50) + '...');
        addDebug('📍 URL actuelle: ' + window.location.href);

        const isCapacitor = window.Capacitor && window.Capacitor.isNativePlatform && window.Capacitor.isNativePlatform();
        addDebug('📱 Environnement Capacitor: ' + (isCapacitor ? 'OUI' : 'NON'));

        function testScheme() {
            addDebug('🧪 Test manuel du scheme...');
            const testUrl = 'trouvetababysitter://auth/callback?success=1';
            addDebug('🔗 Tentative: ' + testUrl);
            window.location.href = testUrl;
        }

        function goToDashboard() {
            addDebug('🏠 Redirection web vers dashboard...');
            window.location.href = '/tableau-de-bord';
        }

        function attemptAppRedirect() {
            addDebug('🚀 Tentative de redirection vers l\'app...');
            
            // Essayer le custom scheme
            const appUrl = 'trouvetababysitter://auth/callback?success=1';
            addDebug('🔗 URL app: ' + appUrl);
            
            try {
                window.location.href = appUrl;
                addDebug('✅ Redirection tentée');
                
                // Vérifier si on est toujours là après un délai
                setTimeout(() => {
                    addDebug('⏰ Vérification retour - toujours sur la page');
                    
                    // Si on est toujours là, c'est que l'app ne s'est pas ouverte
                    if (isCapacitor) {
                        addDebug('📱 Capacitor détecté - l\'app devrait gérer la fermeture');
                    } else {
                        addDebug('🌐 Mode web - redirection fallback vers dashboard');
                        setTimeout(() => goToDashboard(), 2000);
                    }
                }, 3000);
                
            } catch (error) {
                addDebug('❌ Erreur redirection: ' + error.message);
                setTimeout(() => goToDashboard(), 2000);
            }
        }

        // Redirection automatique rapide
        setTimeout(() => {
            addDebug('⏰ Déclenchement redirection automatique...');
            attemptAppRedirect();
        }, 1000); // Réduit à 1 seconde

        // Fallback après 10 secondes
        setTimeout(() => {
            addDebug('⏰ Timeout - redirection fallback vers web');
            goToDashboard();
        }, 10000);

        // Écouter les changements de visibilité
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                addDebug('👁️ Page cachée - possible retour app');
            } else {
                addDebug('👁️ Page visible - retour possible');
            }
        });

        // Écouter focus/blur
        window.addEventListener('blur', () => addDebug('🔄 Window blur - app possiblement ouverte'));
        window.addEventListener('focus', () => addDebug('🔄 Window focus - retour du navigateur'));
    </script>
</body>
</html> 