<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ParentController extends Controller
{
    /**
     * Afficher le profil public d'un parent
     */
    public function show($slug)
    {
        // Extraire l'ID du slug (dernière partie après le dernier tiret)
        $parts = explode('-', $slug);
        $userId = end($parts);

        // Vérifier que l'ID est numérique
        if (!is_numeric($userId)) {
            abort(404, 'Profil introuvable');
        }

        // Récupérer l'utilisateur et vérifier qu'il existe et a le rôle parent
        $parent = User::with([
            'address',
            'ads' => function($query) {
                $query->where('status', 'active')
                      ->orderBy('created_at', 'desc')
                      ->limit(5);
            }
        ])
        ->whereHas('roles', function($query) {
            $query->where('name', 'parent');
        })
        ->find($userId);

        if (!$parent) {
            abort(404, 'Profil parent introuvable');
        }

        // Vérifier que le slug correspond bien à l'utilisateur
        $expectedSlug = $this->createParentSlug($parent);
        if ($slug !== $expectedSlug) {
            // Rediriger vers le bon slug
            return redirect()->route('parent.show', ['slug' => $expectedSlug]);
        }

        return Inertia::render('ParentProfile', [
            'parent' => [
                'id' => $parent->id,
                'firstname' => $parent->firstname,
                'lastname' => $parent->lastname,
                'avatar' => $parent->avatar,
                'created_at' => $parent->created_at,
                'address' => $parent->address ? [
                    'address' => $parent->address->address,
                    'postal_code' => $parent->address->postal_code,
                    'country' => $parent->address->country,
                ] : null,
                'ads' => $parent->ads->map(function($ad) {
                    return [
                        'id' => $ad->id,
                        'title' => $ad->title,
                        'description' => $ad->description,
                        'date_start' => $ad->date_start,
                        'date_end' => $ad->date_end,
                        'hourly_rate' => $ad->hourly_rate,
                        'status' => $ad->status,
                        'slug' => $this->createAdSlug($ad),
                        'created_at' => $ad->created_at,
                    ];
                }),
                'total_ads' => $parent->ads()->where('status', 'active')->count(),
                'member_since' => $parent->created_at->format('Y'),
            ]
        ]);
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
}
