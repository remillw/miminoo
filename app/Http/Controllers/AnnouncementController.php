<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\Address;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements.
     */
    public function index(): Response
    {
        $announcements = Ad::with(['parent', 'address'])
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Annonces', [
            'announcements' => $announcements
        ]);
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
                
                // Étape 4: Détails
                'description' => 'required|string|max:2000',
                
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
                'description' => $validated['description'],
                'address_id' => $address->id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => 'active',
                'additional_data' => [
                    'children' => $validated['children'],
                    'hourly_rate' => $validated['hourly_rate'],
                    'estimated_duration' => $validated['estimated_duration'] ?? 0,
                    'estimated_total' => $validated['estimated_total'] ?? 0,
                ]
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