# 🔐 Guide de Vérification Stripe - Trouve ta Babysitter

## 📋 Vue d'ensemble

Ce guide explique le nouveau système de vérification d'identité intégré qui combine **Stripe Connect** et **Stripe Identity** pour offrir une expérience utilisateur optimale.

## 🎯 Objectifs atteints

✅ **Auto-remplissage complet** : Les données du profil babysitter pré-remplissent automatiquement l'onboarding Stripe  
✅ **Validation d'âge** : Vérification automatique 16+ ans avec rejet si moins  
✅ **Deux options de vérification** : Connect complet ou Identity rapide  
✅ **Interface intégrée** : Plus de redirection forcée vers Stripe  
✅ **Polling automatique** : Détection en temps réel des changements de statut  
✅ **Gestion intelligente** : Reconnaissance de la déconnexion Identity-Connect

## 🔄 Flux de vérification

### Option 1 : Stripe Connect Complet (Recommandé)

```
Utilisateur clique "Finaliser avec Stripe Connect"
    ↓
Création d'un AccountLink avec collect: 'currently_due'
    ↓
Redirection vers Stripe (nouvel onglet)
    ↓
Utilisateur complète : identité + informations bancaires
    ↓
Retour automatique avec statut "completed"
```

### Option 2 : Stripe Identity Rapide

```
Utilisateur clique "Vérification Identity"
    ↓
Création d'une session Identity
    ↓
Redirection vers page interne /identity-verification
    ↓
Widget Stripe Identity intégré
    ↓
Statut "identity_completed_needs_connect"
    ↓
Option de finalisation Connect si nécessaire
```

## 🏗️ Architecture technique

### Backend (Laravel)

#### StripeService.php

- `createVerificationLink()` : Génère un lien Connect avec `collect: 'currently_due'`
- `createIdentityVerificationSession()` : Crée une session Identity
- `getOnboardingStatus()` : Logique intelligente de statut
- `linkIdentityToConnect()` : Tentative de liaison (métadonnées)

#### StripeController.php

- `createVerificationLink()` : API pour lien Connect
- `createIdentityVerificationSession()` : API pour session Identity
- `getOnboardingStatus()` : API pour statut intelligent
- Webhooks configurés pour `identity.verification_session.*`

### Frontend (Vue.js)

#### Payments.vue

- Interface avec deux options clairement présentées
- Polling automatique après vérification
- Gestion des popups bloquées (fallback redirection)
- Nettoyage automatique des paramètres URL

#### IdentityVerification.vue

- Widget Stripe Identity intégré
- Polling du statut toutes les 3 secondes
- Chargement dynamique du script Stripe

## 📊 Statuts intelligents

| Statut                             | Description             | Actions disponibles           |
| ---------------------------------- | ----------------------- | ----------------------------- |
| `not_started`                      | Aucune vérification     | Créer compte Connect          |
| `requires_action`                  | Connect incomplet       | Finaliser Connect ou Identity |
| `identity_completed_needs_connect` | Identity ✅, Connect ⚠️ | Finaliser Connect (optionnel) |
| `completed`                        | Tout finalisé           | Aucune action requise         |
| `error`                            | Erreur système          | Contacter support             |

## 🔧 Configuration requise

### Variables d'environnement

```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### Routes configurées

```php
// Connect
Route::post('/stripe/create-verification-link', [StripeController::class, 'createVerificationLink']);
Route::get('/stripe/onboarding/success', [StripeController::class, 'onboardingSuccess']);

// Identity
Route::post('/stripe/identity/create-session', [StripeIdentityController::class, 'createSession']);
Route::get('/api/stripe/identity/status', [StripeIdentityController::class, 'getStatus']);

// Statut intelligent
Route::get('/api/stripe/onboarding-status', [StripeController::class, 'getOnboardingStatus']);
```

### Webhooks Stripe

```
identity.verification_session.verified
identity.verification_session.requires_input
identity.verification_session.canceled
account.updated
```

## 🧪 Tests

### Test manuel

1. Aller sur `/babysitter/paiements`
2. Tester les deux options de vérification
3. Vérifier le polling automatique
4. Confirmer les redirections de succès

### Test programmatique

```php
$user = User::find(3);
$stripeService = new StripeService();

// Test lien Connect
$verificationLink = $stripeService->createVerificationLink($user);
echo $verificationLink->url;

// Test session Identity
$identitySession = $stripeService->createIdentityVerificationSession($user);
echo $identitySession->url;

// Test statut intelligent
$status = $stripeService->getOnboardingStatus($user);
echo $status['status'];
```

## 🚀 Déploiement

1. **Vérifier les webhooks** : S'assurer que les endpoints sont configurés
2. **Tester en mode test** : Utiliser les clés de test Stripe
3. **Valider l'auto-remplissage** : Vérifier que les données utilisateur sont correctes
4. **Tester les deux flux** : Connect et Identity
5. **Passer en production** : Remplacer par les clés live

## 🔍 Débogage

### Logs utiles

```php
// Statut d'onboarding
Log::info('Onboarding status', ['status' => $status]);

// Sessions Identity
Log::info('Identity session', ['session_id' => $sessionId, 'status' => $status]);

// Webhooks
Log::info('Webhook received', ['type' => $event->type, 'data' => $event->data]);
```

### Problèmes courants

**"Eventually due persiste"** : Normal, Stripe Connect et Identity sont séparés  
**"Popup bloquée"** : Le système fait automatiquement une redirection de fallback  
**"Polling ne s'arrête pas"** : Vérifier que le statut `completed` est bien retourné  
**"Auto-remplissage ne fonctionne pas"** : Vérifier que `date_of_birth` est renseignée

## 📈 Métriques de succès

- ✅ **Taux de completion** : Mesurer le pourcentage d'utilisateurs qui finalisent
- ✅ **Temps de vérification** : Comparer Connect vs Identity
- ✅ **Taux d'abandon** : Identifier les points de friction
- ✅ **Satisfaction utilisateur** : Feedback sur l'expérience

## 🎉 Résultat final

L'utilisateur dispose maintenant de :

- **Expérience fluide** sans redirection forcée
- **Choix de méthode** selon ses préférences
- **Auto-remplissage intelligent** des données
- **Validation d'âge appropriée** (16+ ans)
- **Feedback en temps réel** sur le statut
- **Gestion robuste des erreurs** et cas limites

Le système respecte les contraintes Stripe tout en offrant la meilleure UX possible ! 🚀
