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

        // Récupérer les candidatures de la babysitter avec les relations
        $applications = AdApplication::where('babysitter_id', $user->id)
            ->with([
                'ad' => function ($query) {
                    $query->select('id', 'title', 'description', 'date_start', 'date_end', 'hourly_rate', 'parent_id')
                        ->with(['parent:id,firstname,lastname,avatar']);
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
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
                        'description' => $application->ad->description,
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
            });

        // Récupérer les réservations de la babysitter avec les relations
        $reservations = Reservation::where('babysitter_id', $user->id)
            ->with([
                'ad:id,title,description,date_start,date_end,hourly_rate',
                'parent:id,firstname,lastname,avatar'
            ])
            ->orderBy('service_start_at', 'desc')
            ->get()
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
                        'description' => $reservation->ad->description,
                    ],
                    'parent' => [
                        'id' => $reservation->parent->id,
                        'name' => $reservation->parent->firstname . ' ' . $reservation->parent->lastname,
                        'avatar' => $reservation->parent->avatar,
                    ]
                ];
            });

        return Inertia::render('Babysitting/Index', [
            'applications' => $applications,
            'reservations' => $reservations,
        ]);
    }
} 