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
            max-width: 400px;
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
            margin-top: 2rem;
            font-size: 0.8rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Connexion réussie !</h1>
        <p>Retour vers l'application...</p>
        
        <div class="debug">
            <p id="status">Initialisation...</p>
            <p id="attempts">Tentatives: 0</p>
        </div>
    </div>

    <script>
        // ✅ AMÉLIORATION: Callback mobile plus robuste
        console.log('🔄 Page de callback mobile chargée');
        
        const statusEl = document.getElementById('status');
        const attemptsEl = document.getElementById('attempts');
        let attempts = 0;
        let redirected = false;

        function updateStatus(message) {
            console.log(message);
            if (statusEl) statusEl.textContent = message;
        }

        function updateAttempts() {
            attempts++;
            if (attemptsEl) attemptsEl.textContent = `Tentatives: ${attempts}`;
        }

        // Fonction pour tenter la redirection vers l'app
        function attemptAppRedirect() {
            if (redirected) return;
            
            updateAttempts();
            updateStatus(`Tentative ${attempts}: Redirection vers l'app...`);
            
            try {
                // Construire l'URL avec des paramètres pour le debug
                const appUrl = 'trouvetababysitter://auth/callback?success=1&timestamp=' + Date.now();
                console.log('🔗 Tentative de redirection vers:', appUrl);
                
                window.location.href = appUrl;
                
                // Marquer comme tenté
                setTimeout(() => {
                    if (!redirected && attempts < 3) {
                        updateStatus('Nouvelle tentative...');
                        attemptAppRedirect();
                    }
                }, 2000);
                
            } catch (error) {
                console.error('❌ Erreur lors de la redirection:', error);
                updateStatus('Erreur, redirection vers le web...');
                fallbackToWeb();
            }
        }

        // Fallback vers la version web
        function fallbackToWeb() {
            if (redirected) return;
            redirected = true;
            
            updateStatus('Redirection vers le tableau de bord web...');
            console.log('⚠️ Fallback vers le tableau de bord web');
            
            setTimeout(() => {
                window.location.href = '/tableau-de-bord';
            }, 1000);
        }

        // Démarrer la séquence de redirection
        updateStatus('Préparation de la redirection...');
        
        // Attendre un peu que la page soit complètement chargée
        setTimeout(() => {
            attemptAppRedirect();
            
            // Fallback automatique après 8 secondes
            setTimeout(() => {
                if (!redirected) {
                    updateStatus('Timeout atteint, redirection web...');
                    fallbackToWeb();
                }
            }, 8000);
        }, 500);

        // Écouter les événements de visibilité pour détecter si l'app s'ouvre
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                console.log('✅ Page cachée - L\'app mobile s\'est probablement ouverte');
                redirected = true;
                updateStatus('App ouverte avec succès!');
            }
        });

        // Détecter si l'utilisateur revient sur la page (échec de redirection)
        window.addEventListener('focus', () => {
            if (!redirected && attempts > 0) {
                console.log('⚠️ Retour sur la page - la redirection a échoué');
                updateStatus('Redirection échouée, tentative web...');
                setTimeout(fallbackToWeb, 1000);
            }
        });
    </script>
</body>
</html> 