<?php

namespace App\Http\Controllers;

use App\Models\AdApplication;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BabysittingController extends Controller
{
    /**
     * Affiche la page unifiée des candidatures et réservations pour la babysitter
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Récupérer les paramètres de filtrage
        $applicationStatus = $request->get('application_status', 'all');
        $reservationStatus = $request->get('reservation_status', 'all');
        $dateFilter = $request->get('date_filter', 'upcoming');

        // Construire la requête des candidatures avec filtres
        $applicationsQuery = AdApplication::where('babysitter_id', $user->id)
            ->with([
                'ad' => function ($query) {
                    $query->select('id', 'title', 'additional_info', 'date_start', 'date_end', 'hourly_rate', 'parent_id')
                        ->with(['parent:id,firstname,lastname,avatar']);
                }
            ]);

        // Appliquer le filtre de statut pour les candidatures
        if ($applicationStatus !== 'all' && $applicationStatus !== 'all_including_archived') {
            $applicationsQuery->where('status', $applicationStatus);
        } elseif ($applicationStatus === 'all') {
            // Par défaut, exclure les candidatures archivées
            $applicationsQuery->where('status', '!=', 'archived');
        }
        // Si $applicationStatus === 'all_including_archived', on n'applique aucun filtre

        // Appliquer le filtre de date pour les candidatures
        if ($dateFilter === 'upcoming') {
            $applicationsQuery->whereHas('ad', function($q) {
                $q->where('date_start', '>=', now());
            });
        } elseif ($dateFilter === 'past') {
            $applicationsQuery->whereHas('ad', function($q) {
                $q->where('date_start', '<', now());
            });
        }

        // Trier par date de l'annonce
        if ($dateFilter === 'upcoming') {
            $applicationsQuery->whereHas('ad')->with(['ad' => function($q) {
                $q->orderBy('date_start', 'asc');
            }]);
        } else {
            $applicationsQuery->whereHas('ad')->with(['ad' => function($q) {
                $q->orderBy('date_start', 'desc');
            }]);
        }

        $applications = $applicationsQuery->get()
            ->filter(function ($application) {
                // Filtrer les candidatures sans annonce ou parent
                return $application->ad !== null && $application->ad->parent !== null;
            })
            ->map(function ($application) {
                return [
                    'id' => $application->id,
                    'status' => $application->status,
                    'proposed_rate' => $application->proposed_rate,
                    'counter_rate' => $application->counter_rate,
                    'motivation_note' => $application->motivation_note,
                    'created_at' => $application->created_at,
                    'ad' => [
                        'id' => $application->ad->id,
                        'title' => $application->ad->title,
                        'additional_info' => $application->ad->additional_info,
                        'date_start' => $application->ad->date_start,
                        'date_end' => $application->ad->date_end,
                        'hourly_rate' => $application->ad->hourly_rate,
                        'parent' => [
                            'id' => $application->ad->parent->id,
                            'name' => $application->ad->parent->firstname . ' ' . $application->ad->parent->lastname,
                            'avatar' => $application->ad->parent->avatar,
                        ]
                    ]
                ];
            })
            ->values(); // Réindexer la collection après filtrage

        // Construire la requête des réservations avec filtres
        $reservationsQuery = Reservation::where('babysitter_id', $user->id)
            ->with([
                'ad:id,title,additional_info,date_start,date_end,hourly_rate,address_id',
                'ad.address:id,address,postal_code,latitude,longitude',
                'parent:id,firstname,lastname,avatar'
            ]);

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

        $reservations = $reservationsQuery->get()
            ->filter(function ($reservation) {
                // Filtrer les réservations sans annonce ou parent
                return $reservation->ad !== null && $reservation->parent !== null;
            })
            ->map(function ($reservation) {
                // Vérifier si la babysitter peut laisser un avis
                $canReview = in_array($reservation->status, ['completed', 'service_completed']) && 
                           !$reservation->babysitter_reviewed;
                
                return [
                    'id' => $reservation->id,
                    'status' => $reservation->status,
                    'hourly_rate' => $reservation->hourly_rate,
                    'service_start_at' => $reservation->service_start_at,
                    'service_end_at' => $reservation->service_end_at,
                    'total_amount' => $reservation->total_amount,
                    'babysitter_amount' => $reservation->babysitter_amount,
                    'babysitter_reviewed' => $reservation->babysitter_reviewed,
                    'can_review' => $canReview,
                    'created_at' => $reservation->created_at,
                    'ad' => [
                        'id' => $reservation->ad->id,
                        'title' => $reservation->ad->title,
                        'additional_info' => $reservation->ad->additional_info,
                        'address' => $reservation->ad->address ? [
                            'address' => $reservation->ad->address->address,
                            'postal_code' => $reservation->ad->address->postal_code,
                            'latitude' => $reservation->ad->address->latitude,
                            'longitude' => $reservation->ad->address->longitude,
                        ] : null,
                    ],
                    'parent' => [
                        'id' => $reservation->parent->id,
                        'name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                        'avatar' => $reservation->parent->avatar,
                    ]
                ];
            })
            ->values(); // Réindexer la collection après filtrage

        // Calculer les statistiques
        $allApplications = AdApplication::where('babysitter_id', $user->id)->get();
        $allReservations = Reservation::where('babysitter_id', $user->id)->get();
        
        $stats = [
            'total_applications' => $allApplications->count(),
            'pending_applications' => $allApplications->where('status', 'pending')->count(),
            'archived_applications' => $allApplications->where('status', 'archived')->count(),
            'total_reservations' => $allReservations->count(),
            'completed_reservations' => $allReservations->whereIn('status', ['completed', 'service_completed'])->count(),
            'total_earned' => $allReservations->whereIn('status', ['completed', 'service_completed'])->sum('babysitter_amount'),
        ];

        return Inertia::render('Babysitting/Index', [
            'applications' => $applications,
            'reservations' => $reservations,
            'stats' => $stats,
            'filters' => [
                'application_status' => $applicationStatus,
                'reservation_status' => $reservationStatus,
                'date_filter' => $dateFilter,
            ],
        ]);
    }
} 