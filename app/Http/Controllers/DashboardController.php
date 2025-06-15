<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Mettre à jour automatiquement les statuts des réservations terminées
        $this->updateCompletedReservations();
        
        $user = Auth::user();
        $requestedMode = $request->get('mode');
        
        // Déterminer les rôles de l'utilisateur
        $userRoles = $user->roles->pluck('name')->toArray();
        $hasParentRole = in_array('parent', $userRoles);
        $hasBabysitterRole = in_array('babysitter', $userRoles);
        
        // Charger les profils selon les rôles
        $parentProfile = null;
        $babysitterProfile = null;
        
        if ($hasParentRole) {
            $parentProfile = $user->parentProfile;
        }
        
        if ($hasBabysitterRole) {
            $babysitterProfile = $user->babysitterProfile;
        }
        
        // Données spécifiques selon le mode
        $dashboardData = [];
        
        if ($requestedMode === 'babysitter' && $hasBabysitterRole) {
            $dashboardData = $this->getBabysitterDashboardData($user);
        } elseif ($requestedMode === 'parent' && $hasParentRole) {
            $dashboardData = $this->getParentDashboardData($user);
        } elseif ($hasBabysitterRole && !$hasParentRole) {
            $dashboardData = $this->getBabysitterDashboardData($user);
        } elseif ($hasParentRole && !$hasBabysitterRole) {
            $dashboardData = $this->getParentDashboardData($user);
        } elseif ($hasBabysitterRole) {
            // Par défaut babysitter si les deux rôles
            $dashboardData = $this->getBabysitterDashboardData($user);
        }
        
        // Notifications non lues pour le header
        $unreadNotifications = $user->unreadNotifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'title' => $notification->data['title'] ?? $notification->data['message'] ?? 'Notification',
                    'message' => $notification->data['message'] ?? '',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at
                ];
            });
        
        return Inertia::render('Dashboard', array_merge([
            'user' => $user->load('address'),
            'userRoles' => $userRoles,
            'hasParentRole' => $hasParentRole,
            'hasBabysitterRole' => $hasBabysitterRole,
            'requestedMode' => $requestedMode,
            'parentProfile' => $parentProfile,
            'babysitterProfile' => $babysitterProfile,
            'unreadNotifications' => $unreadNotifications,
            'unreadNotificationsCount' => $user->unreadNotifications()->count(),
        ], $dashboardData));
    }
    
    private function getBabysitterDashboardData(User $user)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Prochaine réservation
        $nextReservation = Reservation::where('babysitter_id', $user->id)
            ->where('service_start_at', '>', now())
            ->whereIn('status', ['paid', 'active'])
            ->with(['parent.address', 'ad.address'])
            ->orderBy('service_start_at')
            ->first();
        
        // Statistiques du mois
        $monthlyReservations = Reservation::where('babysitter_id', $user->id)
            ->where('service_start_at', '>=', $currentMonth)
            ->where('status', 'completed')
            ->get();
        
        $hoursThisMonth = $monthlyReservations->sum(function ($reservation) {
            return Carbon::parse($reservation->service_start_at)
                ->diffInHours(Carbon::parse($reservation->service_end_at));
        });
        
        $earningsThisMonth = $monthlyReservations->sum('babysitter_amount') ?? 0;
        
        // Note moyenne
        $averageRating = $user->averageRating();
        
        // Candidatures récentes (au lieu des annonces)
        $recentApplications = AdApplication::where('babysitter_id', $user->id)
            ->with(['ad' => function($query) {
                $query->select('id', 'title', 'date_start', 'date_end');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($application) {
                return [
                    'id' => $application->id,
                    'title' => $application->ad->title,
                    'date' => $application->ad->date_start->format('Y-m-d'),
                    'time' => $application->ad->date_start->format('H:i') . ' - ' . $application->ad->date_end->format('H:i'),
                    'status' => $this->getApplicationStatusText($application->status),
                    'ad_id' => $application->ad_id
                ];
            });
        
        // Notifications récentes
        $notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'title' => $notification->data['title'] ?? $notification->data['message'] ?? 'Notification',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at
                ];
            });
        
        // Derniers avis reçus
        $recentReviews = Review::where('reviewed_id', $user->id)
            ->with('reviewer')
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'reviewer_name' => $review->reviewer->firstname . ' ' . substr($review->reviewer->lastname, 0, 1) . '.',
                    'created_at' => $review->created_at
                ];
            });
        
        return [
            'stats' => [
                'hours_this_month' => $hoursThisMonth,
                'earnings_this_month' => $earningsThisMonth,
                'average_rating' => round($averageRating, 1)
            ],
            'availability' => [
                'is_available' => $user->babysitterProfile->is_available ?? false
            ],
            'nextReservation' => $nextReservation ? [
                'id' => $nextReservation->id,
                'parent_name' => $nextReservation->parent->firstname . ' ' . $nextReservation->parent->lastname,
                'service_start_at' => $nextReservation->service_start_at,
                'service_end_at' => $nextReservation->service_end_at,
                'address' => $nextReservation->ad->address->full_address ?? 
                           ($nextReservation->parent->address->full_address ?? 'Adresse non spécifiée')
            ] : null,
            'recentAds' => $recentApplications,
            'notifications' => $notifications,
            'recentReviews' => $recentReviews
        ];
    }
    
    private function getParentDashboardData(User $user)
    {
        $currentMonth = Carbon::now()->startOfMonth();
        
        // Prochaine réservation (inclure pending_payment)
        $nextReservation = Reservation::where('parent_id', $user->id)
            ->where('service_start_at', '>', now())
            ->whereIn('status', ['paid', 'active', 'pending_payment'])
            ->with(['babysitter'])
            ->orderBy('service_start_at')
            ->first();
        
        // Statistiques
        $activeAds = Ad::where('parent_id', $user->id)
            ->whereIn('status', ['active', 'booked'])
            ->count();
        
        $bookingsThisMonth = Reservation::where('parent_id', $user->id)
            ->where('service_start_at', '>=', $currentMonth)
            ->whereIn('status', ['paid', 'active', 'completed'])
            ->count();
        
        // Note moyenne des babysitters utilisées
        $averageBabysitterRating = Review::whereHas('reservation', function ($query) use ($user) {
            $query->where('parent_id', $user->id);
        })->where('reviewer_id', $user->id)->avg('rating') ?? 0;
        
        // Dernières annonces (inclure toutes les annonces récentes)
        $recentAds = Ad::where('parent_id', $user->id)
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($ad) {
                return [
                    'id' => $ad->id,
                    'title' => $ad->title,
                    'date' => $ad->date_start->format('Y-m-d'),
                    'time' => $ad->date_start->format('H:i') . ' - ' . $ad->date_end->format('H:i'),
                    'candidates_count' => $ad->applications_count,
                    'status' => $ad->status
                ];
            });
        
        // Notifications récentes (toutes les notifications)
        $notifications = $user->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $this->getNotificationType($notification->type),
                    'title' => $notification->data['title'] ?? $notification->data['message'] ?? 'Notification',
                    'created_at' => $notification->created_at,
                    'read_at' => $notification->read_at
                ];
            });
        
        // Réservations terminées pour lesquelles on peut laisser un avis
        $completedReservations = Reservation::where('parent_id', $user->id)
            ->where('status', 'completed')
            ->whereDoesntHave('review', function ($query) use ($user) {
                $query->where('reviewer_id', $user->id);
            })
            ->with(['babysitter'])
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'babysitter_name' => $reservation->babysitter->firstname . ' ' . substr($reservation->babysitter->lastname, 0, 1) . '.',
                    'babysitter_avatar' => $reservation->babysitter->avatar,
                    'service_date' => $reservation->service_start_at,
                    'can_review' => true
                ];
            });
        
        return [
            'stats' => [
                'active_ads' => $activeAds,
                'bookings_this_month' => $bookingsThisMonth,
                'average_babysitter_rating' => round($averageBabysitterRating, 1)
            ],
            'nextReservation' => $nextReservation ? [
                'id' => $nextReservation->id,
                'babysitter_name' => $nextReservation->babysitter->firstname . ' ' . substr($nextReservation->babysitter->lastname, 0, 1) . '.',
                'babysitter_avatar' => $nextReservation->babysitter->avatar,
                'babysitter_rating' => $nextReservation->babysitter->averageRating(),
                'babysitter_reviews_count' => $nextReservation->babysitter->totalReviews(),
                'service_start_at' => $nextReservation->service_start_at,
                'service_end_at' => $nextReservation->service_end_at,
                'status' => $nextReservation->status
            ] : null,
            'recentAds' => $recentAds,
            'notifications' => $notifications,
            'completedReservations' => $completedReservations
        ];
    }
    
    public function markNotificationAsRead(Request $request, $notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->find($notificationId);
        
        if ($notification) {
            $notification->markAsRead();
        }
        
        return response()->json(['success' => true]);
    }
    
    public function markAllNotificationsAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();
        
        return response()->json(['success' => true]);
    }
    
    private function getNotificationType($notificationType)
    {
        $typeMap = [
            'App\\Notifications\\ReviewRequestNotification' => 'review_request',
            'App\\Notifications\\FundsReleasedNotification' => 'funds_released',
            'App\\Notifications\\DisputeCreatedNotification' => 'dispute_created',
            'App\\Notifications\\NewApplication' => 'new_application',
            'App\\Notifications\\NewMessage' => 'new_message',
            // Ajoutez d'autres types selon vos besoins
        ];
        
        return $typeMap[$notificationType] ?? 'general';
    }

    private function getApplicationStatusText($status)
    {
        $statusMap = [
            'pending' => 'En attente',
            'accepted' => 'Acceptée',
            'declined' => 'Refusée',
            'counter_offered' => 'Contre-offre',
            'expired' => 'Expirée'
        ];

        return $statusMap[$status] ?? 'Inconnu';
    }

    /**
     * Met à jour automatiquement les réservations terminées
     */
    private function updateCompletedReservations()
    {
        $now = Carbon::now();
        
        // Trouver toutes les réservations qui sont terminées mais pas encore marquées comme "completed"
        $reservations = Reservation::where('status', 'paid')
            ->where('service_end_at', '<', $now)
            ->get();
        
        foreach ($reservations as $reservation) {
            $reservation->update(['status' => 'completed']);
            
            \Log::info("Réservation #{$reservation->id} automatiquement mise à jour vers 'completed'", [
                'reservation_id' => $reservation->id,
                'parent_id' => $reservation->parent_id,
                'babysitter_id' => $reservation->babysitter_id,
                'service_end_at' => $reservation->service_end_at
            ]);
        }
    }
}
