<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Address;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Notifications\NewApplication;
use App\Models\Reservation;
use App\Jobs\NotifyBabysittersNewAnnouncement;
use Illuminate\Support\Facades\DB;
use App\Services\StripeService;
use App\Notifications\ReservationCancelled;
use App\Notifications\AnnouncementCancelled;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements with filters.
     */
    public function index(Request $request): Response
    {
        $query = Ad::with(['address'])
            ->withCount('applications') // Ajouter le nombre de candidatures
            ->where('status', 'active') // Seulement les annonces actives (pas expirées, pas réservées, pas terminées)
            ->where('date_start', '>', Carbon::now()) // Exclure les annonces dont la date/heure de début est déjà passée
            ->whereDoesntHave('reservations', function($q) {
                // Exclure les annonces qui ont déjà des réservations confirmées
                $q->whereIn('status', ['paid', 'active', 'completed', 'service_completed']);
            })
            ->where(function($q) {
                // Inclure les annonces normales ET les annonces guests non expirées
                $q->whereNotNull('parent_id')
                  ->orWhere(function($q2) {
                      $q2->where('is_guest', true)
                         ->where('guest_expires_at', '>', now());
                  });
            });
            // Garder toutes les annonces, même celles avec 10 candidatures (sera géré côté frontend)

        // Filtre par tarif minimum
        if ($request->filled('min_rate')) {
            $query->where('hourly_rate', '>=', $request->min_rate);
        }

        // Filtre par âge des enfants
        if ($request->filled('age_range')) {
            $ageRange = $request->age_range;
            
            if ($ageRange === '<3') {
                // Enfants de moins de 3 ans 
                $query->where(function($q) {
                    // Enfants en mois (considérés < 3 ans)
                    $q->whereRaw("JSON_SEARCH(children, 'one', 'mois', NULL, '$[*].unite') IS NOT NULL")
                      // OU enfants en ans de 0, 1 ou 2 ans
                      ->orWhere(function($q2) {
                          $q2->whereRaw("JSON_SEARCH(children, 'one', 'ans', NULL, '$[*].unite') IS NOT NULL")
                             ->whereRaw("(
                                 JSON_SEARCH(children, 'one', '0', NULL, '$[*].age') IS NOT NULL OR
                                 JSON_SEARCH(children, 'one', '1', NULL, '$[*].age') IS NOT NULL OR
                                 JSON_SEARCH(children, 'one', '2', NULL, '$[*].age') IS NOT NULL OR
                                 JSON_SEARCH(children, 'one', 0, NULL, '$[*].age') IS NOT NULL OR
                                 JSON_SEARCH(children, 'one', 1, NULL, '$[*].age') IS NOT NULL OR
                                 JSON_SEARCH(children, 'one', 2, NULL, '$[*].age') IS NOT NULL
                             )");
                      });
                });
            } elseif ($ageRange === '3-6') {
                // Enfants entre 3 et 6 ans (uniquement en ans)
                $query->where(function($q) {
                    $q->whereRaw("JSON_SEARCH(children, 'one', 'ans', NULL, '$[*].unite') IS NOT NULL")
                      ->whereRaw("(
                          JSON_SEARCH(children, 'one', '3', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '4', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '5', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '6', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 3, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 4, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 5, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 6, NULL, '$[*].age') IS NOT NULL
                      )");
                });
            } elseif ($ageRange === '6+') {
                // Enfants de plus de 6 ans (uniquement en ans)
                $query->where(function($q) {
                    $q->whereRaw("JSON_SEARCH(children, 'one', 'ans', NULL, '$[*].unite') IS NOT NULL")
                      ->whereRaw("(
                          JSON_SEARCH(children, 'one', '7', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '8', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '9', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '10', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '11', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '12', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '13', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '14', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', '15', NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 7, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 8, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 9, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 10, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 11, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 12, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 13, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 14, NULL, '$[*].age') IS NOT NULL OR
                          JSON_SEARCH(children, 'one', 15, NULL, '$[*].age') IS NOT NULL
                      )");
                });
            }
        }

        // Filtre par date
        if ($request->filled('date')) {
            $filterDate = Carbon::parse($request->date)->format('Y-m-d');
            $query->whereDate('date_start', $filterDate);
        }

        // Filtre par lieu (recherche dans l'adresse et le code postal)
        if ($request->filled('location')) {
            $location = $request->location;
            $query->whereHas('address', function($q) use ($location) {
                $q->where('address', 'LIKE', "%{$location}%")
                  ->orWhere('postal_code', 'LIKE', "%{$location}%");
            });
        }

        // Filtre par recherche générale
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%")
                  ->orWhereHas('parent', function($q2) use ($search) {
                      $q2->where('firstname', 'LIKE', "%{$search}%")
                         ->orWhere('lastname', 'LIKE', "%{$search}%");
                  })
                  ->orWhereHas('address', function($q2) use ($search) {
                      $q2->where('address', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Géolocalisation - tri par distance si coordonnées fournies
        $userLat = null;
        $userLng = null;
        
        // Vérifier d'abord les coordonnées en session (priorité)
        if (session()->has('user_latitude') && session()->has('user_longitude')) {
            // Vérifier que les coordonnées ne sont pas trop anciennes (max 1 heure)
            $locationSetAt = session('location_set_at');
            if ($locationSetAt && now()->diffInMinutes($locationSetAt) <= 60) {
                $userLat = session('user_latitude');
                $userLng = session('user_longitude');
            }
        }
        
        // Sinon, utiliser les coordonnées de l'URL (pour compatibilité)
        if (!$userLat && !$userLng && $request->filled('latitude') && $request->filled('longitude')) {
            $userLat = $request->latitude;
            $userLng = $request->longitude;
        }
        
        if ($userLat && $userLng) {
            // Calcul de la distance avec la formule haversine
            $query->selectRaw("
                ads.*,
                (6371 * acos(
                    cos(radians(?)) * 
                    cos(radians(addresses.latitude)) * 
                    cos(radians(addresses.longitude) - radians(?)) + 
                    sin(radians(?)) * 
                    sin(radians(addresses.latitude))
                )) AS distance
            ", [$userLat, $userLng, $userLat])
            ->join('addresses', 'ads.address_id', '=', 'addresses.id')
            ->orderBy('distance', 'asc')
            ->orderBy('date_start', 'asc'); // Puis par date de début
        } else {
            // Trier par date de début (les plus proches en premier)
            $query->orderBy('date_start', 'asc')
                  ->orderBy('created_at', 'desc'); // Puis par date de création pour les annonces du même jour
        }

        $announcements = $query->paginate(5);

        // Transformer les données pour inclure les avis du parent et les informations de candidature
        $user = Auth::user();
        $announcements->getCollection()->transform(function ($announcement) use ($user) {
            if ($announcement->isGuest()) {
                // Pour les annonces guests, créer un objet parent fictif
                $guestFirstname = $announcement->guest_firstname ?: 'Parent';
                $announcement->parent = (object) [
                    'id' => 0,
                    'firstname' => $guestFirstname,
                    'lastname' => 'invité',
                    'avatar' => null,
                    'average_rating' => null,
                    'total_reviews' => 0,
                ];
            } else {
                // Charger le parent pour les annonces normales s'il n'est pas déjà chargé
                if (!$announcement->relationLoaded('parent') && $announcement->parent_id) {
                    $announcement->load('parent');
                }
                
                // Vérifier que le parent existe avant de récupérer les avis
                if ($announcement->parent) {
                    // Récupérer les avis du parent pour les annonces normales
                    $parentReviews = \App\Models\Review::where('reviewed_id', $announcement->parent->id)
                        ->where('role', 'babysitter')
                        ->get();

                    // Calculer les statistiques
                    $averageRating = $parentReviews->avg('rating');
                    $totalReviews = $parentReviews->count();

                    // Ajouter les données d'avis à l'annonce
                    $announcement->parent->average_rating = $averageRating ? round($averageRating, 1) : null;
                    $announcement->parent->total_reviews = $totalReviews;
                } else {
                    // Si le parent n'existe pas, créer un objet parent par défaut
                    $announcement->parent = (object) [
                        'id' => 0,
                        'firstname' => 'Parent',
                        'lastname' => 'supprimé',
                        'avatar' => null,
                        'average_rating' => null,
                        'total_reviews' => 0,
                    ];
                }
            }

            // Ajouter les informations de candidature pour l'utilisateur connecté
            $announcement->can_apply = true;
            $announcement->user_application_status = null;
            $announcement->existing_application = null;
            
            if ($user) {
                // Vérifier si c'est sa propre annonce
                if ($announcement->parent_id === $user->id) {
                    $announcement->can_apply = false;
                } else if ($user->hasRole('babysitter')) {
                    // Vérifier s'il a déjà postulé
                    $existingApplication = $announcement->applications()
                        ->where('babysitter_id', $user->id)
                        ->first();
                    
                    if ($existingApplication) {
                        $announcement->user_application_status = $existingApplication->status;
                        // Permettre de repostuler seulement si la candidature est annulée
                        $announcement->can_apply = $existingApplication->status === 'cancelled';
                        
                        // Si la candidature est annulée, passer les données pour repostulation
                        if ($existingApplication->status === 'cancelled') {
                            $announcement->existing_application = [
                                'motivation_note' => $existingApplication->motivation_note,
                                'proposed_rate' => $existingApplication->proposed_rate,
                            ];
                        }
                    } else {
                        // Vérifier si l'annonce n'est pas pleine
                        $announcement->can_apply = $announcement->applications()->count() < 10;
                    }
                } else {
                    // Les parents ne peuvent pas postuler
                    $announcement->can_apply = false;
                }
            }

            return $announcement;
        });

        return Inertia::render('Annonces', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $request->search,
                'min_rate' => $request->min_rate,
                'age_range' => $request->age_range,
                'date' => $request->date,
                'location' => $request->location,
            ],
        ]);
    }

    /**
     * Store a new application for an announcement.
     */
    public function apply(Request $request, Ad $announcement)
    {
        Log::info('🚀 DÉBUT DE POSTULATION', [
            'user_id' => $request->user()?->id,
            'announcement_id' => $announcement->id,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        $user = $request->user();

        // Log des informations utilisateur
        Log::info('👤 INFORMATIONS UTILISATEUR', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_roles' => $user?->roles?->pluck('name'),
            'babysitter_profile_exists' => $user?->babysitterProfile ? true : false,
            'babysitter_verification_status' => $user?->babysitterProfile?->verification_status
        ]);

        // Log des informations sur l'annonce
        Log::info('📋 INFORMATIONS ANNONCE', [
            'announcement_id' => $announcement->id,
            'announcement_status' => $announcement->status,
            'announcement_parent_id' => $announcement->parent_id,
            'announcement_date_start' => $announcement->date_start,
            'announcement_created_at' => $announcement->created_at,
            'now' => now()
        ]);

        // Vérifier si l'annonce est encore active et dans le futur
        if ($announcement->status !== 'active') {
            Log::warning('❌ ANNONCE NON ACTIVE', [
                'announcement_status' => $announcement->status,
                'expected' => 'active'
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cette annonce n\'est plus disponible.'], 400);
            }
            return back()->with('error', 'Cette annonce n\'est plus disponible.');
        }

        if ($announcement->date_start <= now()) {
            Log::warning('❌ ANNONCE DÉJÀ PASSÉE', [
                'announcement_date_start' => $announcement->date_start,
                'now' => now(),
                'difference_minutes' => now()->diffInMinutes($announcement->date_start, false)
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cette annonce a déjà eu lieu ou commence très bientôt.'], 400);
            }
            return back()->with('error', 'Cette annonce a déjà eu lieu ou commence très bientôt.');
        }

        // Vérifier si l'utilisateur est un babysitter
        if (!$user->hasRole('babysitter')) {
            Log::warning('❌ UTILISATEUR PAS BABYSITTER', [
                'user_roles' => $user->roles->pluck('name'),
                'expected_role' => 'babysitter'
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Seuls les babysitters peuvent postuler aux annonces.'], 403);
            }
            return back()->with('error', 'Seuls les babysitters peuvent postuler aux annonces.');
        }

        // Vérifier que l'utilisateur ne postule pas à sa propre annonce (seulement pour les annonces normales)
        if (!$announcement->isGuest() && $announcement->parent_id === $user->id) {
            Log::warning('❌ POSTULATION À SA PROPRE ANNONCE', [
                'user_id' => $user->id,
                'announcement_parent_id' => $announcement->parent_id
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Vous ne pouvez pas postuler à votre propre annonce.'], 400);
            }
            return back()->with('error', 'Vous ne pouvez pas postuler à votre propre annonce.');
        }

        // Vérifier si le profil est vérifié
        if (!$user->babysitterProfile || $user->babysitterProfile->verification_status !== 'verified') {
            Log::warning('❌ PROFIL NON VÉRIFIÉ', [
                'babysitter_profile_exists' => $user->babysitterProfile ? true : false,
                'verification_status' => $user->babysitterProfile?->verification_status,
                'expected_status' => 'verified'
            ]);
            $errorMessage = 'Votre compte n\'est pas vérifié. Vous devez compléter votre profil et demander la vérification avant de pouvoir postuler aux annonces.';
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $errorMessage], 403);
            }
            return back()->with('error', $errorMessage);
        }

        // Vérifier si le compte Stripe Connect est configuré et actif
        if (!$user->stripe_account_id) {
            Log::warning('❌ COMPTE STRIPE NON CONFIGURÉ', [
                'user_id' => $user->id,
                'stripe_account_id' => null
            ]);
            $errorMessage = 'Merci de vous rendre dans "Paiements" pour finaliser votre profil de paiement avant de pouvoir postuler aux annonces.';
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $errorMessage, 'redirect' => route('babysitter.payments')], 403);
            }
            return back()->with('error', $errorMessage);
        }

        // Vérifier si le compte Stripe est prêt pour recevoir des paiements
        try {
            $stripeService = app(\App\Services\StripeService::class);
            $accountDetails = $stripeService->getAccountDetails($user);
            
            if (!$accountDetails || !$accountDetails['charges_enabled'] || !$accountDetails['payouts_enabled']) {
                Log::warning('❌ COMPTE STRIPE NON OPÉRATIONNEL', [
                    'user_id' => $user->id,
                    'stripe_account_id' => $user->stripe_account_id,
                    'charges_enabled' => $accountDetails['charges_enabled'] ?? false,
                    'payouts_enabled' => $accountDetails['payouts_enabled'] ?? false
                ]);
                $errorMessage = 'Votre compte de paiement n\'est pas encore opérationnel. Merci de finaliser votre configuration dans "Paiements".';
                
                if ($request->expectsJson()) {
                    return response()->json(['error' => $errorMessage, 'redirect' => route('babysitter.payments')], 403);
                }
                return back()->with('error', $errorMessage);
            }
        } catch (\Exception $e) {
            Log::error('❌ ERREUR VÉRIFICATION STRIPE', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage()
            ]);
            $errorMessage = 'Impossible de vérifier votre compte de paiement. Merci de vous rendre dans "Paiements" pour vérifier votre configuration.';
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $errorMessage, 'redirect' => route('babysitter.payments')], 403);
            }
            return back()->with('error', $errorMessage);
        }

        // Vérifier si l'utilisateur n'a pas déjà postulé (sauf si candidature annulée)
        $existingApplication = $announcement->applications()->where('babysitter_id', $user->id)->first();
        if ($existingApplication && $existingApplication->status !== 'cancelled') {
            Log::warning('❌ DÉJÀ POSTULÉ (NON ANNULÉ)', [
                'existing_application_id' => $existingApplication->id,
                'existing_application_status' => $existingApplication->status,
                'existing_application_created_at' => $existingApplication->created_at
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Vous avez déjà postulé à cette annonce.'], 400);
            }
            return back()->with('error', 'Vous avez déjà postulé à cette annonce.');
        }

        // Vérifier si l'annonce n'a pas déjà atteint la limite de 10 candidatures
        $applicationsCount = $announcement->applications()->count();
        if ($applicationsCount >= 10) {
            Log::warning('❌ LIMITE DE CANDIDATURES ATTEINTE', [
                'announcement_id' => $announcement->id,
                'applications_count' => $applicationsCount,
                'limit' => 10
            ]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Cette annonce a déjà reçu le nombre maximum de candidatures (10).'], 400);
            }
            return back()->with('error', 'Cette annonce a déjà reçu le nombre maximum de candidatures.');
        }

        // Log des données reçues
        Log::info('📝 DONNÉES REÇUES', [
            'request_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'expects_json' => $request->expectsJson()
        ]);

        // Valider les données avec messages personnalisés
        try {
            $validated = $request->validate([
                'motivation_note' => 'nullable|string|max:1000',
                'proposed_rate' => 'nullable|numeric|min:0|max:999.99',
            ], [
                'motivation_note.max' => 'Le message de motivation ne peut pas dépasser 1000 caractères.',
                'proposed_rate.numeric' => 'Le tarif proposé doit être un nombre valide.',
                'proposed_rate.min' => 'Le tarif proposé ne peut pas être négatif.',
                'proposed_rate.max' => 'Le tarif proposé ne peut pas dépasser 999,99€.',
            ]);

            Log::info('✅ DONNÉES VALIDÉES', [
                'validated_data' => $validated
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('❌ ERREUR VALIDATION', [
                'errors' => $e->errors(),
                'input_data' => $request->all()
            ]);
            if ($request->expectsJson()) {
                $errors = collect($e->errors())->flatten()->implode(' ');
                return response()->json(['error' => $errors], 422);
            }
            throw $e;
        }

        // Créer la candidature ou mettre à jour si repostulation
        try {
            Log::info('💾 CRÉATION/MISE À JOUR CANDIDATURE EN COURS...', [
                'babysitter_id' => $user->id,
                'announcement_id' => $announcement->id,
                'motivation_note' => $validated['motivation_note'] ?? null,
                'proposed_rate' => $validated['proposed_rate'] ?? $announcement->hourly_rate,
                'is_reapplication' => $existingApplication && $existingApplication->status === 'cancelled'
            ]);

            if ($existingApplication && $existingApplication->status === 'cancelled') {
                // Repostulation sur candidature annulée
                $application = $existingApplication;
                $application->update([
                    'status' => 'pending',
                    'motivation_note' => $validated['motivation_note'] ?? null,
                    'proposed_rate' => $validated['proposed_rate'] ?? $announcement->hourly_rate,
                    'expires_at' => now()->addHours(24),
                    'viewed_at' => null,
                    'accepted_at' => null,
                    'counter_rate' => null,
                    'counter_message' => null
                ]);
                
                // Réactiver la conversation si elle existe, sinon la créer
                $application->load('conversation.messages', 'ad');
                
                // Si aucune conversation n'existe, la créer (elle se créera avec le premier message automatiquement)
                if (!$application->conversation) {
                    Conversation::create([
                        'ad_id' => $application->ad_id,
                        'application_id' => $application->id,
                        'parent_id' => $application->ad->parent_id,
                        'babysitter_id' => $application->babysitter_id,
                        'status' => 'pending'
                    ]);
                    
                    // Recharger l'application pour obtenir la nouvelle conversation
                    $application->load('conversation.messages');
                    
                    Log::info('🆕 CONVERSATION CRÉÉE POUR REPOSTULATION', [
                        'conversation_id' => $application->conversation->id,
                        'application_id' => $application->id
                    ]);
                }
                
                if ($application->conversation) {
                    // Réactiver la conversation si elle est archivée
                    if ($application->conversation->status === 'archived') {
                        $application->conversation->update(['status' => 'pending']);
                        
                        Log::info('🔄 CONVERSATION RÉACTIVÉE', [
                            'conversation_id' => $application->conversation->id,
                            'previous_status' => 'archived',
                            'new_status' => 'pending'
                        ]);
                    }
                    
                    // Construire le nouveau message de candidature
                    $newMotivation = $validated['motivation_note'] ?? '';
                    $newRate = $validated['proposed_rate'] ?? $announcement->hourly_rate;
                    
                    // Construire le nouveau message comme dans le boot() method du modèle Conversation
                    $updatedMessage = $newMotivation;
                    if ($newRate) {
                        $updatedMessage .= "\n\nTarif proposé : " . $newRate . "€/h";
                    }
                    
                    // Seulement traiter si on a un message à afficher
                    if (trim($updatedMessage)) {
                        // Chercher le premier message de candidature de la babysitter
                        $firstMessage = $application->conversation->messages()
                            ->where('sender_id', $user->id)
                            ->where('type', 'user')
                            ->orderBy('created_at', 'asc')
                            ->first();
                        
                        if ($firstMessage) {
                            // Mettre à jour le message existant seulement s'il a changé
                            if ($firstMessage->message !== $updatedMessage) {
                                $firstMessage->update([
                                    'message' => $updatedMessage,
                                    'read_at' => null, // Marquer comme non lu pour notifier le parent
                                    'updated_at' => now()
                                ]);
                                
                                // Mettre à jour le timestamp de dernière activité de la conversation
                                $application->conversation->update([
                                    'last_message_at' => now(),
                                    'last_message_by' => $user->id
                                ]);
                                
                                Log::info('📝 PREMIER MESSAGE MIS À JOUR', [
                                    'message_id' => $firstMessage->id,
                                    'conversation_id' => $application->conversation->id,
                                    'old_message' => $firstMessage->getOriginal('message'),
                                    'new_message' => $updatedMessage
                                ]);
                            }
                        } else {
                            // Créer un nouveau premier message s'il n'existe pas
                            $firstMessage = $application->conversation->messages()->create([
                                'sender_id' => $user->id,
                                'message' => $updatedMessage,
                                'type' => 'user',
                                'read_at' => null
                            ]);
                            
                            // Mettre à jour le timestamp de dernière activité de la conversation
                            $application->conversation->update([
                                'last_message_at' => now(),
                                'last_message_by' => $user->id
                            ]);
                            
                            Log::info('🆕 PREMIER MESSAGE CRÉÉ POUR REPOSTULATION', [
                                'message_id' => $firstMessage->id,
                                'conversation_id' => $application->conversation->id,
                                'new_message' => $updatedMessage
                            ]);
                        }
                    }
                }
                
                Log::info('🔄 CANDIDATURE MISE À JOUR (REPOSTULATION)', [
                    'application_id' => $application->id
                ]);
            } else {
                // Nouvelle candidature
                $application = $announcement->applications()->create([
                    'babysitter_id' => $user->id,
                    'status' => 'pending',
                    'motivation_note' => $validated['motivation_note'] ?? null,
                    'proposed_rate' => $validated['proposed_rate'] ?? $announcement->hourly_rate,
                ]);
                Log::info('✅ NOUVELLE CANDIDATURE CRÉÉE', [
                    'application_id' => $application->id
                ]);
            }

            Log::info('✅ CANDIDATURE TRAITÉE AVEC SUCCÈS', [
                'application_id' => $application->id,
                'application_status' => $application->status,
                'created_at' => $application->created_at
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERREUR CRÉATION CANDIDATURE', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Erreur lors de la création de la candidature: ' . $e->getMessage()], 500);
            }
            return back()->with('error', 'Erreur lors de la création de la candidature.');
        }

        // Envoyer les notifications
        try {
            Log::info('📧 ENVOI NOTIFICATIONS EN COURS...', [
                'application_id' => $application->id,
                'is_guest_announcement' => $announcement->isGuest()
            ]);

            if ($announcement->isGuest()) {
                // Pour les annonces guests, envoyer un email directement
                Log::info('📧 NOTIFICATION EMAIL GUEST...', [
                    'guest_email' => $announcement->guest_email
                ]);
                
                \Illuminate\Support\Facades\Notification::route('mail', $announcement->guest_email)
                    ->notify(new NewApplication($application));
                    
                Log::info('✅ NOTIFICATION EMAIL GUEST ENVOYÉE');
            } else {
                // Notifier le parent connecté
                $parent = $announcement->parent;
                if ($parent) {
                    Log::info('📧 NOTIFICATION PARENT...', [
                        'parent_id' => $parent->id,
                        'parent_email' => $parent->email
                    ]);
                    $parent->notify(new NewApplication($application));
                    Log::info('✅ NOTIFICATION PARENT ENVOYÉE');
                } else {
                    Log::warning('⚠️ PARENT INTROUVABLE POUR NOTIFICATION', [
                        'announcement_parent_id' => $announcement->parent_id
                    ]);
                }
            }

            // Note: Le babysitter ne doit PAS recevoir de notification "NewApplication" 
            // car c'est LUI qui a fait la candidature. Une notification de confirmation
            // sera générée automatiquement par le système plus tard si nécessaire.

        } catch (\Exception $e) {
            Log::error('❌ ERREUR ENVOI NOTIFICATIONS', [
                'application_id' => $application->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);
        }

        Log::info('🎉 POSTULATION TERMINÉE AVEC SUCCÈS', [
            'application_id' => $application->id,
            'user_id' => $user->id,
            'announcement_id' => $announcement->id
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Votre candidature a été envoyée avec succès.'], 200);
        }
        return back()->with('success', 'Votre candidature a été envoyée avec succès.');
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(): Response
    {
        $user = Auth::user();
        
        // Récupérer les enfants existants du parent si connecté
        $existingChildren = [];
        if ($user && $user->hasRole('parent') && $user->parentProfile && $user->parentProfile->children_ages) {
            $existingChildren = $user->parentProfile->children_ages;
        }
        
        return Inertia::render('CreateAnnouncement', [
            'user' => $user,
            'role' => $user?->role ?? 'parent',
            'googlePlacesApiKey' => config('services.google.places_api_key'),
            'isGuest' => is_null($user),
            'userEmail' => $user?->email,
            'existingChildren' => $existingChildren,
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(Request $request): RedirectResponse
    {
        // Debug: Log des données reçues
        Log::info('Données reçues pour création annonce:', $request->all());

        try {
            $isGuest = !Auth::check();
            
            // Validation de base pour tous
            $validationRules = [
                // Étape 1: Date et horaires
                'date' => 'required|date|after_or_equal:today',
                'start_time' => 'required|string',
                'end_time' => 'required|string',
                
                // Étape 2: Enfants
                'children' => 'required|array|min:1',
                'children.*.nom' => 'required|string|max:255',
                'children.*.age' => 'required|string|max:3',
                'children.*.unite' => 'required|in:ans,mois',
                
                // Étape 3: Lieu
                'address' => 'required|string|max:500',
                'postal_code' => 'required|string|max:10',
                'country' => 'required|string|max:100',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                
                // Étape 4: Détails (optionnel)
                'additional_info' => 'nullable|string|max:2000',
                
                // Étape 5: Tarif
                'hourly_rate' => 'required|numeric|min:10',
                'estimated_duration' => 'nullable|numeric|min:0',
                'estimated_total' => 'nullable|numeric|min:0',
            ];
            
            // Ajouter validation email et prénom pour les guests
            if ($isGuest) {
                $validationRules['email'] = 'required|email';
                $validationRules['guest_firstname'] = 'required|string|min:2|max:50|regex:/^[a-zA-ZÀ-ÿ\s\'-]+$/';
            }
            
            $validated = $request->validate($validationRules);

            Log::info('Données validées:', $validated);

            // Créer ou récupérer l'adresse avec firstOrCreate
            $address = Address::firstOrCreate([
                'address' => $validated['address'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
            ], [
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
            ]);

            Log::info('Adresse créée/récupérée:', ['address_id' => $address->id]);

            // Créer les dates complètes en gérant les missions de nuit (sur 2 jours)
            $startDateTime = Carbon::parse($validated['date'] . ' ' . $validated['start_time'] . ':00');
            $endDateTime = Carbon::parse($validated['date'] . ' ' . $validated['end_time'] . ':00');
            
            // Si l'heure de fin est plus petite que l'heure de début, 
            // cela signifie que la garde se termine le lendemain
            if ($endDateTime->format('H:i') <= $startDateTime->format('H:i')) {
                $endDateTime->addDay();
            }
            
            $dateStart = $startDateTime->toDateTimeString();
            $dateEnd = $endDateTime->toDateTimeString();

            // Créer un titre automatique
            $childrenCount = count($validated['children']);
            $title = "Garde de {$childrenCount} enfant" . ($childrenCount > 1 ? 's' : '') . 
                    " le " . \Carbon\Carbon::parse($validated['date'])->format('d/m/Y');

            // Créer l'annonce selon le type d'utilisateur
            $announcementData = [
                'title' => $title,
                'address_id' => $address->id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => 'active',
                'children' => $validated['children'],
                'hourly_rate' => $validated['hourly_rate'],
                'estimated_duration' => $validated['estimated_duration'] ?? 0,
                'estimated_total' => $validated['estimated_total'] ?? 0,
                'additional_info' => $validated['additional_info'] ?? null
            ];
            
            if ($isGuest) {
                // Annonce guest
                $announcementData['is_guest'] = true;
                $announcementData['guest_email'] = $validated['email'];
                $announcementData['guest_firstname'] = $validated['guest_firstname'];
                $announcementData['guest_token'] = Ad::generateGuestToken();
                $announcementData['guest_expires_at'] = now()->addDays(30);
                $announcementData['parent_id'] = null;
            } else {
                // Annonce utilisateur connecté
                $announcementData['parent_id'] = Auth::id();
                $announcementData['is_guest'] = false;
            }
            
            $announcement = Ad::create($announcementData);

            Log::info('Annonce créée avec succès:', [
                'ad_id' => $announcement->id, 
                'is_guest' => $isGuest,
                'email' => $isGuest ? $validated['email'] : Auth::user()->email
            ]);


            // Charger l'adresse pour les notifications
            $announcement->load('address');

            // Lancer le job de notification des babysitters en arrière-plan
            try {
                NotifyBabysittersNewAnnouncement::dispatch($announcement);
                Log::info('Job notification babysitters dispatché', ['ad_id' => $announcement->id]);
            } catch (\Exception $e) {
                Log::error('Erreur dispatch job notification babysitters:', [
                    'ad_id' => $announcement->id,
                    'error' => $e->getMessage()
                ]);
                // Ne pas faire échouer la création d'annonce si le dispatch échoue
            }

            // Redirection selon le type d'utilisateur
            if ($isGuest) {
                // Envoyer email de confirmation pour guest
                \Illuminate\Support\Facades\Notification::route('mail', $announcement->guest_email)
                    ->notify(new \App\Notifications\GuestAnnouncementCreated($announcement));
                
                return redirect()
                    ->route('parent.announcements-reservations')
                    ->with('success', 'Votre annonce a été créée avec succès ! Vérifiez votre email pour les instructions.');
            } else {
                return redirect()
                    ->route('parent.announcements-reservations')
                    ->with('success', 'Annonce créée avec succès !');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Erreur de validation:', ['errors' => $e->errors()]);
            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de l\'annonce:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Une erreur est survenue lors de la création de l\'annonce.');
        }
    }

    /**
     * Display the specified announcement.
     */
    public function show($slug, Request $request)
    {
        // Extraire l'ID du slug (dernière partie après le dernier tiret)
        $parts = explode('-', $slug);
        $adId = end($parts);

        // Vérifier que l'ID est numérique
        if (!is_numeric($adId)) {
            abort(404);
        }

        // Déterminer si on peut afficher les annonces expirées/annulées
        $fromMessaging = $request->get('from') === 'messaging' || 
                        $request->hasHeader('referer') && 
                        str_contains($request->header('referer'), 'messagerie');
        
        $user = Auth::user();
        $canViewExpired = $fromMessaging && $user;

        // Récupérer l'annonce avec ses relations enrichies
        $query = Ad::with(['parent.parentProfile', 'address']);
        
        if ($canViewExpired) {
            // Si on vient de la messagerie, permettre de voir les annonces expirées/annulées
            $query->whereIn('status', ['active', 'expired', 'cancelled', 'booked', 'service_completed', 'completed']);
        } else {
            // Sinon, seulement les annonces actives
            $query->where('status', 'active');
        }
        
        $announcement = $query->findOrFail($adId);

        // Vérifier que le slug correspond bien à l'annonce
        $expectedSlug = $this->createAdSlug($announcement);
        if ($slug !== $expectedSlug) {
            // Rediriger vers le bon slug
            return redirect()->route('announcements.show', ['slug' => $expectedSlug]);
        }

        // Récupérer les avis du parent (en tant que parent, pas babysitter)
        $parentReviews = \App\Models\Review::where('reviewed_id', $announcement->parent->id)
            ->where('role', 'babysitter') // Avis donnés par des babysitters
            ->with(['reviewer'])
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                    'reviewer' => [
                        'firstname' => $review->reviewer->firstname,
                        'lastname' => substr($review->reviewer->lastname, 0, 1) . '.',
                        'avatar' => $review->reviewer->avatar,
                    ]
                ];
            });

        // Calculer les statistiques des avis
        $reviewStats = [
            'average_rating' => round($parentReviews->avg('rating') ?? 0, 1),
            'total_reviews' => $parentReviews->count(),
            'rating_distribution' => []
        ];

        // Distribution des notes (1-5 étoiles)
        for ($i = 1; $i <= 5; $i++) {
            $count = $parentReviews->where('rating', $i)->count();
            $reviewStats['rating_distribution'][$i] = [
                'count' => $count,
                'percentage' => $parentReviews->count() > 0 ? round(($count / $parentReviews->count()) * 100) : 0
            ];
        }

        // Vérifier si l'utilisateur connecté peut postuler et son statut de candidature
        $canApply = true;
        $userApplicationStatus = null;
        $existingApplicationData = null;
        
        if ($user) {
            // Vérifier si c'est sa propre annonce
            if ($announcement->parent_id === $user->id) {
                $canApply = false;
            } else if ($user->hasRole('babysitter')) {
                // Vérifier s'il a déjà postulé
                $existingApplication = $announcement->applications()
                    ->where('babysitter_id', $user->id)
                    ->first();
                
                if ($existingApplication) {
                    $userApplicationStatus = $existingApplication->status;
                    // Permettre de repostuler seulement si la candidature est annulée
                    $canApply = $existingApplication->status === 'cancelled';
                    
                    // Si la candidature est annulée, passer les données pour repostulation
                    if ($existingApplication->status === 'cancelled') {
                        $existingApplicationData = [
                            'motivation_note' => $existingApplication->motivation_note,
                            'proposed_rate' => $existingApplication->proposed_rate,
                        ];
                    }
                } else {
                    // Vérifier si l'annonce n'est pas pleine
                    $canApply = $announcement->applications()->count() < 10;
                }
            } else {
                // Les parents ne peuvent pas postuler
                $canApply = false;
            }
        }

        // Détecter si c'est une mission multi-jours
        $startDate = \Carbon\Carbon::parse($announcement->date_start);
        $endDate = \Carbon\Carbon::parse($announcement->date_end);
        $isMultiDay = !$startDate->isSameDay($endDate);
        
        // Calculer la durée en jours et heures pour multi-jours
        $duration = [
            'is_multi_day' => $isMultiDay,
            'total_hours' => $announcement->estimated_duration,
            'days' => $isMultiDay ? $startDate->diffInDays($endDate) + 1 : 1,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $startDate->format('H:i'),
            'end_time' => $endDate->format('H:i')
        ];

        return Inertia::render('AnnouncementDetail', [
            'announcement' => [
                'id' => $announcement->id,
                'title' => $announcement->title,
                'description' => $announcement->additional_info,
                'date_start' => $announcement->date_start,
                'date_end' => $announcement->date_end,
                'hourly_rate' => $announcement->hourly_rate,
                'estimated_duration' => $announcement->estimated_duration,
                'estimated_total' => $announcement->estimated_total,
                'status' => $announcement->status,
                'children' => $announcement->children,
                'created_at' => $announcement->created_at,
                'slug' => $expectedSlug,
                'duration' => $duration,
                'can_apply' => $canApply,
                'user_application_status' => $userApplicationStatus,
                'existing_application' => $existingApplicationData,
                'parent' => [
                    'id' => $announcement->parent->id,
                    'firstname' => $announcement->parent->firstname,
                    'lastname' => $announcement->parent->lastname,
                    'avatar' => $announcement->parent->avatar,
                    'slug' => $this->createParentSlug($announcement->parent),
                    'reviews' => $parentReviews,
                    'review_stats' => $reviewStats,
                    'member_since' => $announcement->parent->created_at,
                ],
                'address' => [
                    'address' => $announcement->address->address,
                    'postal_code' => $announcement->address->postal_code,
                    'country' => $announcement->address->country,
                    'latitude' => $announcement->address->latitude,
                    'longitude' => $announcement->address->longitude,
                ],
            ]
        ]);
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit(Ad $announcement)
    {
        // Vérifier que l'utilisateur peut modifier cette annonce
        if ($announcement->parent_id !== Auth::id()) {
            abort(403);
        }

        // Vérifier que l'annonce peut être modifiée
        $hasAcceptedApplications = $announcement->applications()
            ->whereIn('status', ['accepted', 'counter_accepted'])
            ->exists();
        
        if ($hasAcceptedApplications) {
            return redirect()->route('parent.announcements-reservations')
                ->with('error', 'Cette annonce ne peut plus être modifiée car elle a des candidatures acceptées.');
        }

        if (new \Carbon\Carbon($announcement->date_start) < now()) {
            return redirect()->route('parent.announcements-reservations')
                ->with('error', 'Cette annonce ne peut plus être modifiée car la date est passée.');
        }

        $announcement->load(['address']);

        // Garder les enfants au format original {nom, age, unite} pour l'édition
        $announcementData = $announcement->toArray();
        // Les enfants sont déjà au bon format dans l'annonce

        return Inertia::render('EditAnnouncement', [
            'announcement' => $announcementData
        ]);
    }

    /**
     * Update the specified announcement.
     */
    public function update(Request $request, Ad $announcement): RedirectResponse
    {
        // Vérifier que l'utilisateur peut modifier cette annonce
        if ($announcement->parent_id !== Auth::id()) {
            abort(403);
        }

        // Vérifier que l'annonce peut être modifiée
        $hasAcceptedApplications = $announcement->applications()
            ->whereIn('status', ['accepted', 'counter_accepted'])
            ->exists();
        
        if ($hasAcceptedApplications) {
            return redirect()->route('parent.announcements-reservations')
                ->with('error', 'Cette annonce ne peut plus être modifiée car elle a des candidatures acceptées.');
        }

        $validated = $request->validate([
            'additional_info' => 'nullable|string|max:2000',
            'date_start' => 'required|date|after_or_equal:today',
            'date_end' => 'required|date|after:date_start',
            'hourly_rate' => 'required|numeric|min:10',
            'children' => 'required|array|min:1',
            'children.*.nom' => 'required|string|max:255',
            'children.*.age_range' => 'required|string',
        ]);

        // Générer automatiquement le titre basé sur les dates et enfants
        $startDate = new \Carbon\Carbon($validated['date_start']);
        $endDate = new \Carbon\Carbon($validated['date_end']);
        $childrenNames = collect($validated['children'])->pluck('nom')->join(', ');
        $validated['title'] = "Garde de {$childrenNames} le " . $startDate->format('d/m/Y') . " de " . $startDate->format('H:i') . " à " . $endDate->format('H:i');

        // Convertir les données enfants du format composant vers le format DB
        $convertedChildren = collect($validated['children'])->map(function ($child) {
            // Convertir de {nom: "Léa", age_range: "2-3-ans"} vers {nom: "Léa", age: "2", unite: "ans"}
            $ageRange = $child['age_range'];
            
            switch ($ageRange) {
                case '0-6-mois':
                    return ['nom' => $child['nom'], 'age' => '3', 'unite' => 'mois'];
                case '6-12-mois':
                    return ['nom' => $child['nom'], 'age' => '9', 'unite' => 'mois'];
                case '1-2-ans':
                    return ['nom' => $child['nom'], 'age' => '1', 'unite' => 'ans'];
                case '2-3-ans':
                    return ['nom' => $child['nom'], 'age' => '2', 'unite' => 'ans'];
                case '3-6-ans':
                    return ['nom' => $child['nom'], 'age' => '4', 'unite' => 'ans'];
                case '6-10-ans':
                    return ['nom' => $child['nom'], 'age' => '8', 'unite' => 'ans'];
                case '10-ans-plus':
                    return ['nom' => $child['nom'], 'age' => '12', 'unite' => 'ans'];
                default:
                    // Fallback pour un format non reconnu
                    return ['nom' => $child['nom'], 'age' => '3', 'unite' => 'ans'];
            }
        });

        // Préparer les données à mettre à jour
        $updateData = $validated;
        $updateData['children'] = $convertedChildren->toArray();

        $announcement->update($updateData);

        return redirect()
            ->route('parent.announcements-reservations')
            ->with('success', 'Annonce mise à jour avec succès !');
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Ad $announcement): RedirectResponse
    {
        // Vérifier que l'utilisateur peut supprimer cette annonce
        if ($announcement->parent_id !== Auth::id()) {
            abort(403);
        }

        $announcement->delete();

        return redirect()
            ->route('announcements.index')
            ->with('success', 'Annonce supprimée avec succès !');
    }

    /**
     * Show user's own announcements.
     */
    public function myAnnouncements(): Response
    {
        $announcements = Ad::with(['address'])
            ->where('parent_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Announcements/MyAnnouncements', [
            'announcements' => $announcements
        ]);
    }

    /**
     * Show user's announcements and reservations combined.
     */
    public function myAnnouncementsAndReservations(Request $request): Response
    {
        $user = Auth::user();
        
        // Récupérer les paramètres de filtrage
        $announcementStatus = $request->get('announcement_status', 'all');
        $reservationStatus = $request->get('reservation_status', 'all');
        $dateFilter = $request->get('date_filter', 'upcoming');

        // Construire la requête des annonces avec filtres
        $announcementsQuery = Ad::with(['address', 'applications' => function($query) {
                $query->with(['babysitter' => function($q) {
                    $q->select('id', 'firstname', 'lastname', 'avatar');
                }]);
            }])
            ->where('parent_id', $user->id);

        // Appliquer le filtre de statut pour les annonces
        if ($announcementStatus !== 'all') {
            $announcementsQuery->where('status', $announcementStatus);
        } else {
            // Par défaut, exclure les expirées, annulées et finalisées
            $announcementsQuery->whereIn('status', ['active', 'booked', 'service_completed']);
        }

        // Appliquer le filtre de date pour les annonces
        if ($dateFilter === 'upcoming') {
            $announcementsQuery->where('date_start', '>=', now());
        } elseif ($dateFilter === 'past') {
            $announcementsQuery->where('date_start', '<', now());
        }

        // Trier par date (plus proche à plus loin pour les prochaines, plus récent à plus ancien pour les passées)
        if ($dateFilter === 'upcoming') {
            $announcementsQuery->orderBy('date_start', 'asc');
        } else {
            $announcementsQuery->orderBy('date_start', 'desc');
        }

        $allAnnouncements = $announcementsQuery->paginate(10);
        
        $announcements = $allAnnouncements->getCollection()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'date_start' => $ad->date_start,
                    'date_end' => $ad->date_end,
                    'hourly_rate' => $ad->hourly_rate,
                    'status' => $ad->status,
                    'applications_count' => $ad->applications->count(),
                    'applications' => $ad->applications->map(function ($app) {
                        return [
                            'id' => $app->id,
                            'status' => $app->status,
                            'proposed_rate' => $app->proposed_rate,
                            'counter_rate' => $app->counter_rate,
                            'babysitter' => [
                                'id' => $app->babysitter->id,
                                'name' => $app->babysitter->firstname . ' ' . $app->babysitter->lastname,
                                'avatar' => $app->babysitter->avatar,
                            ]
                        ];
                    }),
                    'estimated_duration' => $ad->estimated_duration,
                    'estimated_total' => $ad->estimated_total,
                    'address' => [
                        'address' => $ad->address->address,
                        'postal_code' => $ad->address->postal_code,
                    ],
                ];
            });

        // Construire la requête des réservations avec filtres
        $reservationsQuery = Reservation::with(['babysitter', 'ad'])
            ->where('parent_id', $user->id);

        // Appliquer le filtre de statut pour les réservations
        if ($reservationStatus !== 'all') {
            $reservationsQuery->where('status', $reservationStatus);
        }

        // Appliquer le filtre de date pour les réservations
        if ($dateFilter === 'upcoming') {
            $reservationsQuery->where('service_start_at', '>=', now());
        } elseif ($dateFilter === 'past') {
            $reservationsQuery->where('service_start_at', '<', now());
        }

        // Trier par date (plus proche à plus loin pour les prochaines, plus récent à plus ancien pour les passées)
        if ($dateFilter === 'upcoming') {
            $reservationsQuery->orderBy('service_start_at', 'asc');
        } else {
            $reservationsQuery->orderBy('service_start_at', 'desc');
        }

        $allReservations = $reservationsQuery->paginate(10);
        
        $reservations = $allReservations->getCollection()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'hourly_rate' => $reservation->hourly_rate,
                    'deposit_amount' => $reservation->deposit_amount,
                    'service_fee' => $reservation->service_fee,
                    'total_deposit' => $reservation->total_deposit,
                    'babysitter_amount' => $reservation->babysitter_amount,
                    'service_start_at' => $reservation->service_start_at,
                    'service_end_at' => $reservation->service_end_at,
                    'paid_at' => $reservation->paid_at,
                    'can_be_cancelled' => $reservation->can_be_cancelled,
                    'can_be_reviewed' => $reservation->can_be_reviewed_by_parent,
                    'babysitter' => [
                        'id' => $reservation->babysitter->id,
                        'name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                        'avatar' => $reservation->babysitter->avatar,
                    ],
                    'ad' => $reservation->ad ? [
                        'id' => $reservation->ad->id,
                        'title' => $reservation->ad->title,
                        'date_start' => $reservation->ad->date_start,
                        'date_end' => $reservation->ad->date_end,
                    ] : null
                ];
            });

        // Statistiques globales (sans filtres)
        $globalAnnouncementsQuery = Ad::where('parent_id', $user->id);
        $globalReservationsQuery = Reservation::where('parent_id', $user->id);
        
        $stats = [
            'total_announcements' => $globalAnnouncementsQuery->count(),
            'active_announcements' => $globalAnnouncementsQuery->where('status', 'active')->count(),
            'total_reservations' => $globalReservationsQuery->count(),
            'completed_reservations' => $globalReservationsQuery->whereIn('status', ['completed', 'service_completed'])->count(),
            'total_spent' => $globalReservationsQuery->whereIn('status', ['completed', 'service_completed', 'paid'])->sum('total_deposit'),
        ];

        return Inertia::render('Parent/AnnouncementsAndReservations', [
            'announcements' => [
                'data' => $announcements,
                'current_page' => $allAnnouncements->currentPage(),
                'last_page' => $allAnnouncements->lastPage(),
                'total' => $allAnnouncements->total(),
                'per_page' => $allAnnouncements->perPage(),
            ],
            'reservations' => [
                'data' => $reservations,
                'current_page' => $allReservations->currentPage(),
                'last_page' => $allReservations->lastPage(),
                'total' => $allReservations->total(),
                'per_page' => $allReservations->perPage(),
            ],
            'stats' => $stats,
            'filters' => [
                'announcement_status' => $announcementStatus,
                'reservation_status' => $reservationStatus,
                'date_filter' => $dateFilter,
            ],
        ]);
    }

    /**
     * Créer un slug pour une annonce
     */
    private function createAdSlug($ad): string
    {
        if (!$ad) return '';
        
        $date = $ad->date_start->format('Y-m-d');
        $title = $ad->title ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $ad->title)) : 'annonce';
        
        $slug = trim($date . '-' . $title . '-' . $ad->id, '-');
        return preg_replace('/-+/', '-', $slug);
    }

    /**
     * Créer un slug pour un parent
     */
    private function createParentSlug(User $user): string
    {
        $firstName = $user->firstname ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->firstname)) : 'parent';
        $lastName = $user->lastname ? 
            strtolower(preg_replace('/[^a-z0-9]/i', '-', $user->lastname)) : '';
        
        $slug = trim($firstName . '-' . $lastName . '-' . $user->id, '-');
        return preg_replace('/-+/', '-', $slug);
    }

    /**
     * Cancel an entire announcement
     */
    public function cancel(Request $request, Ad $announcement)
    {
        $user = Auth::user();

        Log::info('=== ANNULATION ANNONCE COMPLÈTE ===', [
            'user_id' => $user->id,
            'announcement_id' => $announcement->id,
            'current_status' => $announcement->status
        ]);

        // Vérifier que l'utilisateur peut annuler cette annonce
        if ($announcement->parent_id !== $user->id) {
            abort(403);
        }

        // Vérifier que l'annonce peut être annulée
        if ($announcement->status !== 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Cette annonce ne peut plus être annulée'
                ], 400);
            }
            return back()->with('error', 'Cette annonce ne peut plus être annulée');
        }

        $validated = $request->validate([
            'reason' => 'required|in:found_other_solution,no_longer_needed,date_changed,budget_issues,other',
            'note' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            // 1. Marquer l'annonce comme annulée
            $announcement->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
                'cancellation_note' => $validated['note']
            ]);

            // 2. Récupérer toutes les candidatures actives
            $applications = $announcement->applications()
                ->with(['babysitter', 'conversation.reservation'])
                ->whereIn('status', ['pending', 'accepted', 'counter_offered'])
                ->get();

            $refundedReservations = [];
            $archivedApplications = [];

            // 3. Traiter chaque candidature
            foreach ($applications as $application) {
                // Marquer la candidature comme annulée d'abord
                $application->update([
                    'status' => 'cancelled', // Utiliser 'cancelled' au lieu de 'archived'
                    'cancelled_at' => now(),
                    'cancellation_reason' => 'parent_cancelled_announcement'
                ]);
                $archivedApplications[] = $application->id;

                // Archiver la conversation
                if ($application->conversation) {
                    $application->conversation->update(['status' => 'cancelled']);

                    // Si il y a une réservation payée, gérer le remboursement
                    if ($application->conversation->reservation && 
                        $application->conversation->reservation->status === 'paid') {
                        
                        $reservation = $application->conversation->reservation;
                        
                        // Annuler la réservation et gérer le remboursement
                        $reservation->update([
                            'status' => 'cancelled_by_parent',
                            'cancelled_at' => now(),
                            'cancellation_reason' => 'parent_cancelled_announcement',
                            'cancellation_note' => $validated['note']
                        ]);
                        
                        // Remboursement avec le nouveau système
                        $refundAmount = $reservation->getRefundAmount();
                        if ($refundAmount > 0 && $reservation->stripe_payment_intent_id) {
                            // Tentative de remboursement avec déduction babysitter
                            try {
                                $refund = app(StripeService::class)->createRefundWithBabysitterDeduction(
                                    $reservation->stripe_payment_intent_id,
                                    $reservation,
                                    'Annulation de l\'annonce par le parent'
                                );
                                
                                $reservation->update([
                                    'refund_amount' => $reservation->getParentRefundAmount(),
                                    'refunded_at' => now()
                                ]);
                                
                                $refundedReservations[] = [
                                    'reservation_id' => $reservation->id,
                                    'babysitter_id' => $reservation->babysitter_id,
                                    'parent_refund_amount' => $reservation->getParentRefundAmount(),
                                    'babysitter_deduction' => $reservation->getBabysitterDeductionAmount()
                                ];
                                
                            } catch (\Exception $e) {
                                Log::error('Erreur remboursement lors annulation annonce', [
                                    'reservation_id' => $reservation->id,
                                    'payment_intent_id' => $reservation->stripe_payment_intent_id,
                                    'error' => $e->getMessage()
                                ]);
                                // Ne pas faire échouer l'annulation pour un problème de remboursement
                            }
                        }

                        // Envoyer notification à la babysitter
                        $reservation->babysitter->notify(new \App\Notifications\ReservationCancelled(
                            $reservation,
                            'parent',
                            'parent_cancelled_announcement',
                            "L'annonce complète a été annulée par le parent. Raison : " . $validated['note']
                        ));
                    }
                }

                // Envoyer notification générale à la babysitter sur l'annulation de l'annonce
                $application->babysitter->notify(new \App\Notifications\AnnouncementCancelled(
                    $announcement,
                    $validated['reason'],
                    $validated['note']
                ));
            }

            DB::commit();

            Log::info('Annonce annulée avec succès', [
                'announcement_id' => $announcement->id,
                'applications_archived' => count($archivedApplications),
                'reservations_refunded' => count($refundedReservations)
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Annonce annulée avec succès',
                    'announcement' => [
                        'id' => $announcement->id,
                        'status' => $announcement->status,
                        'cancelled_at' => $announcement->cancelled_at->toISOString(),
                        'applications_archived' => count($archivedApplications),
                        'reservations_refunded' => count($refundedReservations)
                    ]
                ]);
            }
            
            return back()->with('success', 'Annonce annulée avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Erreur lors de l\'annulation de l\'annonce', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Erreur lors de l\'annulation de l\'annonce'
                ], 500);
            }
            
            return back()->with('error', 'Erreur lors de l\'annulation de l\'annonce');
        }
    }
} 