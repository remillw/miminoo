<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\BabysitterProfile;
use App\Models\Review;
use App\Models\Reservation;
use App\Models\Address;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Get admin stats for sidebar badges
     */
    protected function getAdminStats()
    {
        return [
            'pending_verifications' => BabysitterProfile::where('verification_status', 'pending')->count(),
            'unread_contacts' => Contact::where('status', 'unread')->count(),
        ];
    }

    public function dashboard()
    {
        // Statistiques pour le dashboard
        $stats = [
            'total_users' => User::count(),
            'total_parents' => User::whereHas('roles', function($q) {
                $q->where('name', 'parent');
            })->count(),
            'total_babysitters' => User::whereHas('roles', function($q) {
                $q->where('name', 'babysitter');
            })->count(),
            'pending_verifications' => BabysitterProfile::where('verification_status', 'pending')->count(),
            'verified_babysitters' => BabysitterProfile::where('verification_status', 'verified')->count(),
            'total_ads' => Ad::count(),
            'active_ads' => Ad::where('status', 'active')->count(),
            'total_reservations' => Reservation::count(),
            'total_reviews' => Review::count(),
            'recent_registrations' => User::where('created_at', '>=', now()->subDays(7))->count(),
            
            // Statistiques Stripe Connect
            'stripe_total_accounts' => User::whereNotNull('stripe_account_id')->count(),
            'stripe_active_accounts' => User::where('stripe_account_status', 'active')->count(),
            'stripe_pending_accounts' => User::where('stripe_account_status', 'pending')->count(),
            'stripe_rejected_accounts' => User::where('stripe_account_status', 'rejected')->count(),

            // Statistiques des contacts non lus
            'unread_contacts' => Contact::where('status', 'unread')->count(),
        ];

        // Activité récente
        $recentActivity = [
            'new_users' => User::with('roles')->latest()->take(5)->get(),
            'recent_ads' => Ad::with(['parent', 'address'])->latest()->take(5)->get(),
            'recent_reviews' => Review::with(['reviewer', 'reviewed'])->latest()->take(5)->get(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentActivity' => $recentActivity
        ]);
    }

    // Gestion des parents
    public function parents(Request $request)
    {
        $query = User::with(['address', 'parentProfile'])
            ->whereHas('roles', function($q) {
                $q->where('name', 'parent');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $parents = $query->withCount(['ads', 'reviews as given_reviews_count'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Parents', [
            'parents' => $parents,
            'filters' => $request->only(['search']),
            'stats' => $this->getAdminStats()
        ]);
    }

    // Gestion des babysitters
    public function babysitters(Request $request)
    {
        $query = User::with(['address', 'babysitterProfile'])
            ->whereHas('roles', function($q) {
                $q->where('name', 'babysitter');
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('firstname', 'like', "%{$search}%")
                  ->orWhere('lastname', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->whereHas('babysitterProfile', function($q) use ($request) {
                $q->where('verification_status', $request->status);
            });
        }

        $babysitters = $query->withCount(['applications', 'reviews as received_reviews_count'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Babysitters', [
            'babysitters' => $babysitters,
            'filters' => $request->only(['search', 'status']),
            'stats' => $this->getAdminStats()
        ]);
    }

    // Gestion des annonces
    public function announcements(Request $request)
    {
        $query = Ad::with(['parent', 'address'])
            ->withCount(['applications', 'reservations']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('parent', function($q2) use ($search) {
                      $q2->where('firstname', 'like', "%{$search}%")
                         ->orWhere('lastname', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $announcements = $query->latest()->paginate(20);

        return Inertia::render('Admin/Announcements', [
            'announcements' => $announcements,
            'filters' => $request->only(['search', 'status']),
            'stats' => $this->getAdminStats()
        ]);
    }

    // Gestion des avis
    public function reviews(Request $request)
    {
        $query = Review::with(['reviewer', 'reviewed', 'reservation.ad'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                  ->orWhereHas('reviewer', function($q2) use ($search) {
                      $q2->where('firstname', 'like', "%{$search}%")
                         ->orWhere('lastname', 'like', "%{$search}%");
                  })
                  ->orWhereHas('reviewed', function($q2) use ($search) {
                      $q2->where('firstname', 'like', "%{$search}%")
                         ->orWhere('lastname', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate(20);

        return Inertia::render('Admin/Reviews', [
            'reviews' => $reviews,
            'filters' => $request->only(['search', 'rating']),
            'stats' => $this->getAdminStats()
        ]);
    }

    // Voir un utilisateur
    public function showUser($id)
    {
        $user = User::with(['roles', 'address', 'parentProfile', 'babysitterProfile', 'ads', 'applications', 'reviews'])
            ->findOrFail($id);

        return Inertia::render('Admin/UserDetail', [
            'user' => $user
        ]);
    }

    // Créer un utilisateur
    public function createUser()
    {
        return Inertia::render('Admin/CreateUser');
    }

    // Stocker un nouvel utilisateur
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:parent,babysitter',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            // Créer l'adresse si fournie
            $addressId = null;
            if ($request->filled('address')) {
                $address = Address::create([
                    'address' => $validated['address'],
                    'postal_code' => $validated['postal_code'] ?? '',
                    'country' => $validated['country'] ?? 'France',
                ]);
                $addressId = $address->id;
            }

            // Créer l'utilisateur
            $user = User::create([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'],
                'address_id' => $addressId,
                'status' => 'approved',
                'email_verified_at' => now(),
            ]);

            // Assigner les rôles
            foreach ($validated['roles'] as $roleName) {
                $user->assignRole($roleName);
                
                if ($roleName === 'parent') {
                    \App\Models\ParentProfile::create(['user_id' => $user->id]);
                } elseif ($roleName === 'babysitter') {
                    \App\Models\BabysitterProfile::create([
                        'user_id' => $user->id,
                        'verification_status' => 'pending'
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'Utilisateur créé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Erreur lors de la création de l\'utilisateur'])
                ->withInput();
        }
    }

    // Éditer un utilisateur
    public function editUser($id)
    {
        $user = User::with(['roles', 'address', 'parentProfile', 'babysitterProfile'])
            ->findOrFail($id);

        return Inertia::render('Admin/EditUser', [
            'user' => $user
        ]);
    }

    // Mettre à jour un utilisateur
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'status' => 'required|in:pending,approved,suspended',
            'roles' => 'required|array|min:1',
            'roles.*' => 'in:parent,babysitter,admin',
        ]);

        DB::beginTransaction();

        try {
            // Mettre à jour l'utilisateur
            $user->update([
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => $validated['status'],
            ]);

            // Gérer les rôles
            $currentRoles = $user->roles->pluck('name')->toArray();
            $newRoles = $validated['roles'];

            // Supprimer les rôles qui ne sont plus présents
            foreach ($currentRoles as $role) {
                if (!in_array($role, $newRoles)) {
                    $user->removeRole($role);
                    
                    // Supprimer les profils correspondants
                    if ($role === 'parent') {
                        $user->parentProfile?->delete();
                    } elseif ($role === 'babysitter') {
                        $user->babysitterProfile?->delete();
                    }
                }
            }

            // Ajouter les nouveaux rôles
            foreach ($newRoles as $role) {
                if (!in_array($role, $currentRoles)) {
                    $user->assignRole($role);
                    
                    // Créer les profils correspondants
                    if ($role === 'parent') {
                        \App\Models\ParentProfile::firstOrCreate(['user_id' => $user->id]);
                    } elseif ($role === 'babysitter') {
                        \App\Models\BabysitterProfile::firstOrCreate([
                            'user_id' => $user->id,
                            'verification_status' => 'pending'
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('admin.users.show', $user)
                ->with('success', 'Utilisateur mis à jour avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()
                ->withErrors(['error' => 'Erreur lors de la mise à jour'])
                ->withInput();
        }
    }

    // Supprimer un utilisateur
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        DB::beginTransaction();

        try {
            // Supprimer les profils associés
            $user->parentProfile?->delete();
            $user->babysitterProfile?->delete();
            
            // Supprimer l'utilisateur (les relations seront gérées par les contraintes FK)
            $user->delete();

            DB::commit();

            return redirect()
                ->route('admin.parents')
                ->with('success', 'Utilisateur supprimé avec succès');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Erreur lors de la suppression']);
        }
    }

    // Éditer une annonce
    public function editAnnouncement($id)
    {
        $announcement = Ad::with(['parent', 'address', 'applications.babysitter'])
            ->findOrFail($id);

        return Inertia::render('Admin/EditAnnouncement', [
            'announcement' => $announcement
        ]);
    }

    // Mettre à jour une annonce
    public function updateAnnouncement(Request $request, $id)
    {
        $announcement = Ad::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'status' => 'required|in:active,paused,completed,cancelled',
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        $announcement->update($validated);

        return redirect()
            ->route('admin.announcements')
            ->with('success', 'Annonce mise à jour avec succès');
    }

    // Supprimer une annonce
    public function destroyAnnouncement($id)
    {
        $announcement = Ad::findOrFail($id);
        $announcement->delete();

        return redirect()
            ->route('admin.announcements')
            ->with('success', 'Annonce supprimée avec succès');
    }

    // Supprimer un avis
    public function destroyReview($id)
    {
        $review = Review::findOrFail($id);
        $review->delete();

        return redirect()
            ->route('admin.reviews')
            ->with('success', 'Avis supprimé avec succès');
    }
}
