<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use App\Notifications\ContactReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ContactController extends Controller
{
    /**
     * Display the contact form
     */
    public function index()
    {
        return Inertia::render('Contact');
    }

    /**
     * Store a new contact message
     */
    public function store(Request $request)
    {
        Log::info('=== NOUVELLE DEMANDE DE CONTACT ===', [
            'email' => $request->email,
            'subject' => $request->subject,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'subject' => 'required|in:recherche,inscription,tarifs,technique,amélioration,autre',
            'message' => 'required|string|max:2000',
        ]);

        try {
            // Créer la demande de contact
            $contact = Contact::create($validated);

            Log::info('Contact créé avec succès', [
                'contact_id' => $contact->id,
                'email' => $contact->email,
                'subject' => $contact->subject
            ]);

            // Notifier tous les admins
            $admins = User::role('admin')->get();
            
            if ($admins->count() > 0) {
                Notification::send($admins, new ContactReceived($contact));
                Log::info('Notifications envoyées aux admins', [
                    'contact_id' => $contact->id,
                    'admin_count' => $admins->count()
                ]);
            } else {
                Log::warning('Aucun admin trouvé pour notification', [
                    'contact_id' => $contact->id
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Votre message a été envoyé avec succès ! Notre équipe vous répondra dans les plus brefs délais.'
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du contact', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'subject' => $request->subject
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l\'envoi de votre message. Veuillez réessayer.'
            ], 500);
        }
    }

    /**
     * Display contacts in admin panel
     */
    public function adminIndex(Request $request)
    {
        $query = Contact::query();

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('subject')) {
            $query->where('subject', $request->subject);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(20);

        return Inertia::render('Admin/Contacts', [
            'contacts' => $contacts,
            'filters' => [
                'status' => $request->status,
                'subject' => $request->subject,
                'search' => $request->search,
            ],
            'stats' => [
                'total' => Contact::count(),
                'unread' => Contact::unread()->count(),
                'recent' => Contact::recent()->count(),
            ]
        ]);
    }

    /**
     * Show a specific contact
     */
    public function show(Contact $contact)
    {
        // Marquer comme lu si pas encore lu
        if ($contact->status === 'unread') {
            $contact->markAsRead();
        }

        return Inertia::render('Admin/ContactDetail', [
            'contact' => $contact
        ]);
    }

    /**
     * Update contact status/notes
     */
    public function update(Request $request, Contact $contact)
    {
        $validated = $request->validate([
            'status' => 'required|in:unread,read,replied',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $contact->update($validated);

        return redirect()->route('admin.contacts.show', $contact)
            ->with('success', 'Contact mis à jour avec succès');
    }

    /**
     * Delete a contact
     */
    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('admin.contacts.index')
            ->with('success', 'Contact supprimé avec succès');
    }
}
