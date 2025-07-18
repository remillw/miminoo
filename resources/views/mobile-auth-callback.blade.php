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
    </style>
</head>
<body>
    <div class="container">
        <div class="spinner"></div>
        <h1>Connexion réussie !</h1>
        <p>Retour vers l'application...</p>
    </div>

    <script>
        // Redirection automatique vers l'app mobile
        console.log('🔄 Redirection vers l\'app mobile...');
        
        // Essayer la redirection vers le custom scheme
        window.location.href = 'trouvetababysitter://auth/callback?success=1';
        
        // Fallback après 2 secondes si le scheme ne fonctionne pas
        setTimeout(() => {
            console.log('⚠️ Fallback vers le tableau de bord web');
            window.location.href = '/tableau-de-bord';
        }, 2000);
    </script>
</body>
</html> 