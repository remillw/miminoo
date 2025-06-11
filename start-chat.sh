#!/bin/bash

# Script pour démarrer le chat en temps réel Miminoo
echo "🚀 Démarrage du système de chat temps réel Miminoo..."

# Vérifier que les dépendances sont installées
if ! command -v laravel-echo-server &> /dev/null; then
    echo "❌ Laravel Echo Server n'est pas installé."
    echo "Installer avec: yarn global add laravel-echo-server"
    exit 1
fi

# Vider les caches
echo "🧹 Nettoyage des caches..."
php artisan config:clear > /dev/null 2>&1
php artisan route:clear > /dev/null 2>&1

# Démarrer les services en parallèle
echo "🔧 Démarrage des services..."

# Démarrer Laravel Echo Server en arrière-plan
echo "📡 Démarrage de Laravel Echo Server (port 6001)..."
laravel-echo-server start --dev &
ECHO_PID=$!

# Attendre un peu que Echo Server démarre
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
echo "   - Laravel Echo Server: http://localhost:6001"
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
    kill $ECHO_PID 2>/dev/null
    kill $VITE_PID 2>/dev/null
    kill $LARAVEL_PID 2>/dev/null
    echo "✅ Tous les services ont été arrêtés"
    exit 0
}

# Capturer Ctrl+C
trap cleanup SIGINT

# Attendre indéfiniment
wait 