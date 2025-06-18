<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Address;
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

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements with filters.
     */
    public function index(Request $request): Response
    {
        $query = Ad::with(['parent', 'address'])
            ->where('status', 'active')
            ->where('date_start', '>=', now());

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
            ->orderBy('distance', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $announcements = $query->paginate(12);

        return Inertia::render('Annonces', [
            'announcements' => $announcements,
            'filters' => [
                'search' => $request->search,
                'min_rate' => $request->min_rate,
                'age_range' => $request->age_range,
                'date' => $request->date,
                'location' => $request->location,
            ]
        ]);
    }

    /**
     * Store a new application for an announcement.
     */
    public function apply(Request $request, Ad $announcement)
    {
        $user = $request->user();

        // Vérifier si l'utilisateur est un babysitter
        if (!$user->hasRole('babysitter')) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Seuls les babysitters peuvent postuler aux annonces.'], 403);
            }
            return back()->with('error', 'Seuls les babysitters peuvent postuler aux annonces.');
        }

        // Vérifier si le profil est vérifié
        if (!$user->babysitterProfile || $user->babysitterProfile->verification_status !== 'verified') {
            $errorMessage = 'Votre compte n\'est pas vérifié. Vous devez compléter votre profil et demander la vérification avant de pouvoir postuler aux annonces.';
            
            if ($request->expectsJson()) {
                return response()->json(['error' => $errorMessage], 403);
            }
            return back()->with('error', $errorMessage);
        }

        // Vérifier si l'utilisateur n'a pas déjà postulé
        if ($announcement->applications()->where('babysitter_id', $user->id)->exists()) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Vous avez déjà postulé à cette annonce.'], 400);
            }
            return back()->with('error', 'Vous avez déjà postulé à cette annonce.');
        }

        // Valider les données
        $validated = $request->validate([
            'motivation_note' => 'nullable|string|max:1000',
            'proposed_rate' => 'nullable|numeric|min:0|max:999.99',
        ]);

        // Créer la candidature
        $application = $announcement->applications()->create([
            'babysitter_id' => $user->id,
            'status' => 'pending',
            'motivation_note' => $validated['motivation_note'] ?? null,
            'proposed_rate' => $validated['proposed_rate'] ?? $announcement->hourly_rate,
        ]);

        // Envoyer les notifications
        try {
            // Notifier le parent
            $parent = $announcement->parent;
            if ($parent) {
                $parent->notify(new NewApplication($application));
            }

            // Notifier le babysitter (confirmation)
            $user->notify(new NewApplication($application));
        } catch (\Exception $e) {
            Log::error('Erreur envoi notification candidature:', [
                'application_id' => $application->id,
                'error' => $e->getMessage()
            ]);
        }

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
        
        return Inertia::render('CreateAnnouncement', [
            'user' => $user,
            'role' => $user->role ?? 'parent',
            'googlePlacesApiKey' => config('services.google.places_api_key'),
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
            $validated = $request->validate([
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
                'hourly_rate' => 'required|numeric|min:0|max:999.99',
                'estimated_duration' => 'nullable|numeric|min:0',
                'estimated_total' => 'nullable|numeric|min:0',
            ]);

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

            // Créer les dates complètes
            $dateStart = $validated['date'] . ' ' . $validated['start_time'] . ':00';
            $dateEnd = $validated['date'] . ' ' . $validated['end_time'] . ':00';

            // Créer un titre automatique
            $childrenCount = count($validated['children']);
            $title = "Garde de {$childrenCount} enfant" . ($childrenCount > 1 ? 's' : '') . 
                    " le " . \Carbon\Carbon::parse($validated['date'])->format('d/m/Y');

            // Créer l'annonce
            $announcement = Ad::create([
                'parent_id' => Auth::id(),
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
            ]);

            Log::info('Annonce créée avec succès:', ['ad_id' => $announcement->id]);

            return redirect()
                ->route('announcements.index')
                ->with('success', 'Annonce créée avec succès !');

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
    public function show($slug)
    {
        // Extraire l'ID du slug (dernière partie après le dernier tiret)
        $parts = explode('-', $slug);
        $adId = end($parts);

        // Vérifier que l'ID est numérique
        if (!is_numeric($adId)) {
            abort(404);
        }

        // Récupérer l'annonce avec ses relations
        $announcement = Ad::with(['parent', 'address'])
            ->where('status', 'active')
            ->findOrFail($adId);

        // Vérifier que le slug correspond bien à l'annonce
        $expectedSlug = $this->createAdSlug($announcement);
        if ($slug !== $expectedSlug) {
            // Rediriger vers le bon slug
            return redirect()->route('announcements.show', ['slug' => $expectedSlug]);
        }

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
                'parent' => [
                    'id' => $announcement->parent->id,
                    'firstname' => $announcement->parent->firstname,
                    'lastname' => $announcement->parent->lastname,
                    'avatar' => $announcement->parent->avatar,
                    'slug' => $this->createParentSlug($announcement->parent),
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
    public function edit(Ad $announcement): Response
    {
        // Vérifier que l'utilisateur peut modifier cette annonce
        if ($announcement->parent_id !== Auth::id()) {
            abort(403);
        }

        $announcement->load(['address']);

        return Inertia::render('Announcements/Edit', [
            'announcement' => $announcement
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'date_start' => 'required|date|after_or_equal:today',
            'date_end' => 'required|date|after:date_start',
            'status' => ['required', Rule::in(['active', 'awaiting_payment', 'booked', 'completed', 'cancelled'])],
        ]);

        $announcement->update($validated);

        return redirect()
            ->route('announcements.show', $announcement)
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
    public function myAnnouncementsAndReservations(): Response
    {
        $user = Auth::user();

        // Récupérer les annonces du parent avec leurs candidatures
        $announcements = Ad::with(['address', 'applications' => function($query) {
                $query->with(['babysitter' => function($q) {
                    $q->select('id', 'firstname', 'lastname', 'avatar');
                }]);
            }])
            ->where('parent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
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

        // Récupérer les réservations du parent
        $reservations = Reservation::with(['babysitter', 'ad'])
            ->where('parent_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
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
                    'can_be_reviewed' => $reservation->can_be_reviewed,
                    'babysitter' => [
                        'id' => $reservation->babysitter->id,
                        'name' => $reservation->babysitter->firstname . ' ' . $reservation->babysitter->lastname,
                        'avatar' => $reservation->babysitter->avatar,
                    ],
                    'ad' => [
                        'id' => $reservation->ad->id,
                        'title' => $reservation->ad->title,
                        'date_start' => $reservation->ad->date_start,
                        'date_end' => $reservation->ad->date_end,
                    ]
                ];
            });

        // Statistiques
        $stats = [
            'total_announcements' => $announcements->count(),
            'active_announcements' => $announcements->where('status', 'active')->count(),
            'total_reservations' => $reservations->count(),
            'completed_reservations' => $reservations->where('status', 'completed')->count(),
            'total_spent' => $reservations->where('status', 'completed')->sum('total_deposit'),
        ];

        return Inertia::render('Parent/AnnouncementsAndReservations', [
            'announcements' => $announcements,
            'reservations' => $reservations,
            'stats' => $stats,
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
} 