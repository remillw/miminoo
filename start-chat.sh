#!/bin/bash

# Script pour démarrer le chat en temps réel Miminoo avec Reverb
echo "🚀 Démarrage du système de chat temps réel Miminoo..."

# Vider les caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1
php artisan view:clear > /dev/null 2>&1

# Vérifier la configuration Reverb
echo "🔧 Vérification de la configuration..."
php artisan config:cache > /dev/null 2>&1

# Démarrer les services en parallèle
echo "🔧 Démarrage des services..."

# Démarrer Laravel Reverb (WebSocket server) en arrière-plan
echo "📡 Démarrage de Laravel Reverb (port 8080)..."
php artisan reverb:start --debug &
REVERB_PID=$!

# Démarrer le worker de queue en arrière-plan
echo "⚙️ Démarrage du worker de queue..."
php artisan queue:work --daemon &
QUEUE_PID=$!

# Attendre un peu que Reverb démarre
sleep 3

# Démarrer Vite en arrière-plan
echo "⚡ Démarrage de Vite (dev server)..."
yarn dev &
VITE_PID=$!

# Démarrer le serveur Laravel
echo "🐘 Démarrage du serveur Laravel (port 8000)..."
php artisan serve &
LARAVEL_PID=$!

echo ""
echo "✅ Tous les services sont démarrés !"
echo ""
echo "📊 Services actifs :"
echo "   - Laravel Reverb (WebSocket): http://localhost:8080"
echo "   - Queue Worker: Actif"
echo "   - Vite Dev Server: http://localhost:5173"
echo "   - Laravel Server: http://localhost:8000"
echo ""
echo "🎯 Votre application est accessible sur: http://localhost:8000"
echo ""
echo "❌ Pour arrêter tous les services, appuyez sur Ctrl+C"

# Fonction pour nettoyer les processus à l'arrêt
cleanup() {
    echo ""
    echo "🛑 Arrêt des services..."
    kill $REVERB_PID 2>/dev/null
    kill $QUEUE_PID 2>/dev/null
    kill $VITE_PID 2>/dev/null
    kill $LARAVEL_PID 2>/dev/null
    echo "✅ Tous les services ont été arrêtés"
    exit 0
}

# Capturer Ctrl+C
trap cleanup SIGINT

# Attendre indéfiniment
wait 