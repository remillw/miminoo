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
        // Vérifier que l'utilisateur est connecté
        if (!Auth::check()) {
            return response()->json(['error' => 'Vous devez être connecté pour postuler'], 401);
        }

        $user = Auth::user();

        // Vérifier que l'utilisateur a le rôle babysitter
        if (!$user->hasRole('babysitter')) {
            return response()->json(['error' => 'Seuls les babysitters peuvent postuler à cette annonce'], 403);
        }

        // Vérifier que l'utilisateur ne postule pas à sa propre annonce
        if ($announcement->parent_id === $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas postuler à votre propre annonce'], 403);
        }

        // Vérifier qu'il n'a pas déjà postulé
        $existingApplication = AdApplication::where('ad_id', $announcement->id)
            ->where('babysitter_id', $user->id)
            ->first();

        if ($existingApplication) {
            return response()->json(['error' => 'Vous avez déjà postulé à cette annonce'], 409);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:500',
            'proposed_rate' => 'required|numeric|min:0|max:999.99',
        ]);

        try {
            $application = AdApplication::create([
                'ad_id' => $announcement->id,
                'babysitter_id' => $user->id,
                'motivation_note' => $validated['message'],
                'proposed_rate' => $validated['proposed_rate'],
                'status' => 'pending'
            ]);

            Log::info('Candidature créée:', [
                'application_id' => $application->id,
                'ad_id' => $announcement->id,
                'babysitter_id' => $user->id
            ]);

            return response()->json([
                'message' => 'Candidature envoyée avec succès !',
                'application' => $application
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création de la candidature:', [
                'error' => $e->getMessage(),
                'ad_id' => $announcement->id,
                'user_id' => $user->id
            ]);

            return response()->json(['error' => 'Une erreur est survenue lors de l\'envoi de votre candidature'], 500);
        }
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create(): Response
    {
        $user = Auth::user();
        
        return Inertia::render('CreateAnnouncement', [
            'user' => $user,
            'role' => $user->role ?? 'parent'
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
    public function show(Ad $announcement): Response
    {
        $announcement->load(['parent', 'address']);
        
        return Inertia::render('Announcements/Show', [
            'announcement' => $announcement
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
} 