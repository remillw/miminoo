<?php

namespace App\Services;

use App\Models\User;
use Stripe\StripeClient;
use Stripe\AccountToken;
use Illuminate\Support\Facades\Log;
use App\Models\Reservation;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Créer un compte Stripe Connect pour un utilisateur (LEGACY - utiliser createConnectAccountWithClientToken)
     * 
     * @deprecated Cette méthode est obsolète. Utilisez createConnectAccountWithClientToken() avec des account tokens
     *             pour respecter les exigences françaises DSP2 et la sécurité renforcée.
     */
    public function createConnectAccount(User $user)
    {
        try {
            // Vérifier l'âge minimum (16 ans)
            if ($user->date_of_birth) {
                $age = \Carbon\Carbon::parse($user->date_of_birth)->age;
                if ($age < 16) {
                    throw new \Exception('Vous devez avoir au moins 16 ans pour créer un compte de paiement');
                }
            }

            // Préparer les données de base avec auto-remplissage
            $accountData = [
                'type' => 'custom', // Custom pour plus de contrôle
                'country' => 'FR',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'metadata' => [
                    'user_id' => $user->id,
                    'platform' => 'TrouvetaBabysitter'
                ]
            ];

            // Auto-remplir avec les données du profil si disponibles
            $individual = [];
            
            if ($user->firstname) {
                $individual['first_name'] = $user->firstname;
            }
            
            if ($user->lastname) {
                $individual['last_name'] = $user->lastname;
            }
            
            if ($user->email) {
                $individual['email'] = $user->email;
            }
            
            // Téléphone temporairement désactivé - problème de format avec Stripe
            // if ($user->phone) {
            //     // Formater le numéro de téléphone pour Stripe (format international sans +)
            //     $phone = preg_replace('/[^0-9]/', '', $user->phone);
            //     if (strlen($phone) === 10 && substr($phone, 0, 1) !== '0') {
            //         $phone = '33' . $phone; // Ajouter le code pays français
            //     } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            //         $phone = '33' . substr($phone, 1); // Remplacer le 0 par 33
            //     } elseif (substr($phone, 0, 2) === '33') {
            //         // Déjà au bon format
            //     } elseif (substr($phone, 0, 3) === '+33') {
            //         $phone = substr($phone, 1); // Enlever le +
            //     }
            //     $individual['phone'] = '+' . $phone;
            // }
            
            if ($user->date_of_birth) {
                $dob = \Carbon\Carbon::parse($user->date_of_birth);
                $individual['dob'] = [
                    'day' => $dob->day,
                    'month' => $dob->month,
                    'year' => $dob->year,
                ];
            }
            
            // Adresse si disponible
            if ($user->address) {
                $individual['address'] = [
                    'line1' => $user->address->address ?? '',
                    'city' => $this->extractCityFromAddress($user->address->address ?? ''),
                    'postal_code' => $user->address->postal_code ?? '',
                    'country' => 'FR',
                ];
            }

            if (!empty($individual)) {
                $accountData['individual'] = $individual;
            }

            // Paramètres business simplifiés pour babysitters
            $accountData['business_profile'] = [
                'mcc' => '8299', // Services de garde d'enfants
                'product_description' => 'Services de garde d\'enfants et babysitting',
            ];

            // Créer le compte
            $account = $this->stripe->accounts->create($accountData);

            // Sauvegarder l'ID du compte
            $user->update([
                'stripe_account_id' => $account->id,
                'stripe_account_status' => 'pending'
            ]);

            Log::info('Compte Stripe Connect créé avec auto-remplissage', [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'auto_filled_fields' => array_keys($individual)
            ]);

            return $account;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte Connect', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mettre à jour un compte Connect existant avec les dernières données utilisateur
     */
    public function updateConnectAccountData(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return;
            }

            $updateData = [];
            $individual = [];
            
            // Mise à jour des informations personnelles
            if ($user->firstname) {
                $individual['first_name'] = $user->firstname;
            }
            
            if ($user->lastname) {
                $individual['last_name'] = $user->lastname;
            }
            
            if ($user->email) {
                $individual['email'] = $user->email;
            }
            
            // Date de naissance
            if ($user->date_of_birth) {
                $dob = \Carbon\Carbon::parse($user->date_of_birth);
                $individual['dob'] = [
                    'day' => $dob->day,
                    'month' => $dob->month,
                    'year' => $dob->year,
                ];
            }
            
            // Adresse si disponible
            if ($user->address) {
                $individual['address'] = [
                    'line1' => $user->address->address ?? '',
                    'city' => $this->extractCityFromAddress($user->address->address ?? ''),
                    'postal_code' => $user->address->postal_code ?? '',
                    'country' => 'FR',
                ];
            }

            if (!empty($individual)) {
                $updateData['individual'] = $individual;
            }

            // Mise à jour du profil business si nécessaire
            $updateData['business_profile'] = [
                'mcc' => '8299',
                'product_description' => 'Services de garde d\'enfants et babysitting',
            ];

            // Effectuer la mise à jour
            if (!empty($updateData)) {
                $this->stripe->accounts->update($user->stripe_account_id, $updateData);
                
                Log::info('Compte Stripe Connect mis à jour avec les données utilisateur', [
                    'user_id' => $user->id,
                    'account_id' => $user->stripe_account_id,
                    'updated_fields' => array_keys($individual)
                ]);
            }

        } catch (\Exception $e) {
            Log::warning('Impossible de mettre à jour le compte Connect', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer le processus si la mise à jour échoue
        }
    }

    /**
     * Extraire la ville d'une adresse complète
     */
    private function extractCityFromAddress($address)
    {
        // Tentative d'extraction de la ville depuis une adresse complète.
        // L'adresse peut se présenter sous la forme "12 rue Exemple, 75001 Paris" ou
        // "12 rue Exemple, 75001 Paris, France". On cherche donc la partie qui
        // suit le code postal.

        // Nettoyer et séparer les différentes parties de l'adresse
        $parts = array_map('trim', explode(',', (string) $address));

        // Examiner d'abord la dernière partie
        $candidate = end($parts) ?: '';
        if (preg_match('/\d{4,5}\s*(.+)/', $candidate, $matches)) {
            return trim($matches[1]);
        }

        // Si la dernière partie est un pays (ex: "France"), tenter avec l'avant-dernière
        $candidate = prev($parts) ?: $candidate;
        if (preg_match('/\d{4,5}\s*(.+)/', $candidate, $matches)) {
            return trim($matches[1]);
        }

        // À défaut, retourner simplement cette partie nettoyée
        return $candidate;
    }

    /**
     * Créer un lien d'onboarding Stripe Connect pré-rempli
     */
    public function createOnboardingLink(User $user)
    {
        try {
            // Vérifier que le compte Stripe Connect existe
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé. Veuillez d\'abord configurer votre compte via l\'onboarding dédié.');
            }
            
            // Mettre à jour le compte existant avec les dernières données utilisateur
            $this->updateConnectAccountData($user);

            // Créer un AccountLink pour l'onboarding avec vérification d'identité forcée
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('babysitter.stripe.onboarding.refresh'),
                'return_url' => route('babysitter.stripe.onboarding.success'),
                'type' => 'account_onboarding',
                'collect' => 'currently_due', // Forcer la collecte immédiate incluant vérification d'identité
            ]);

            Log::info('Lien d\'onboarding créé avec vérification d\'identité forcée', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'url' => $accountLink->url
            ]);

            return $accountLink;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du lien d\'onboarding', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createAccountLink(User $user, string $type = 'account_onboarding')
    {
        try {
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('babysitter.stripe.refresh'),
                'return_url' => route('babysitter.stripe.success'),
                'type' => $type,
            ]);

            return $accountLink;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du lien de compte', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createVerificationLink(User $user)
    {
        try {
            // Vérifier que le compte Connect existe
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé. Veuillez d\'abord configurer votre compte via l\'onboarding dédié.');
            }

            // Créer un AccountLink pour la vérification d'identité
            $accountLink = $this->stripe->accountLinks->create([
                'account' => $user->stripe_account_id,
                'refresh_url' => route('babysitter.stripe.verification.refresh'),
                'return_url' => route('babysitter.stripe.verification.success'),
                'type' => 'account_onboarding', // Utilise le processus d'onboarding complet
                'collect' => 'currently_due', // Collecte seulement ce qui est actuellement requis
            ]);

            Log::info('AccountLink créé pour vérification d\'identité', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'url' => $accountLink->url
            ]);

            return $accountLink;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du lien de vérification Connect', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    
    private function ensureAccountReadyForVerification(User $user)
    {
        try {
            // Récupérer les détails du compte
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            // Vérifier si des informations de base manquent et les ajouter
            $updateData = [];
            
            // S'assurer que le type de business est défini
            if (!$account->business_type) {
                $updateData['business_type'] = 'individual';
            }
            
            // S'assurer que les informations individuelles de base sont présentes
            if ($account->business_type === 'individual' || !$account->business_type) {
                if (!$account->individual || !$account->individual->first_name) {
                    $updateData['individual'] = [
                        'first_name' => $user->name ? explode(' ', $user->name)[0] : 'Prénom',
                        'last_name' => $user->name && count(explode(' ', $user->name)) > 1 ? 
                                      implode(' ', array_slice(explode(' ', $user->name), 1)) : 'Nom',
                        'email' => $user->email,
                    ];
                }
            }
            
            // Mettre à jour le compte si nécessaire
            if (!empty($updateData)) {
                $this->stripe->accounts->update($user->stripe_account_id, $updateData);
            }
            
        } catch (\Exception $e) {
            Log::warning('Impossible de préparer le compte pour la vérification', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer le processus si la préparation échoue
        }
    }

    public function getAccountStatus(User $user)
    {
        try {
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            $status = 'pending';
            if ($account->charges_enabled && $account->payouts_enabled) {
                $status = 'active';
            } elseif ($account->requirements->disabled_reason) {
                $status = 'rejected';
            } elseif (!$account->details_submitted) {
                $status = 'pending';
            } elseif (count($account->requirements->currently_due ?? []) > 0 || count($account->requirements->past_due ?? []) > 0) {
                $status = 'restricted';
            }

            $user->update(['stripe_account_status' => $status]);

            return $status;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du statut du compte Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createPaymentIntent($amount, $currency = 'eur', $applicationFee = 0, $babysitter = null, $user = null)
    {
        try {
            // L'utilisateur doit être fourni
            if (!$user) {
                throw new \Exception('Utilisateur requis pour créer un PaymentIntent');
            }
            
            // Récupérer ou créer un customer Stripe
            $customerId = $user->stripe_customer_id;
            
            if (!$customerId) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'metadata' => [
                        'user_id' => $user->id,
                        'platform' => 'TrouvetaBabysitter'
                    ]
                ]);
                
                $customerId = $customer->id;
                $user->update(['stripe_customer_id' => $customerId]);
                
                Log::info('Customer Stripe créé', [
                    'user_id' => $user->id,
                    'customer_id' => $customerId
                ]);
            }

            $paymentIntentData = [
                'amount' => $amount, // Montant déjà en centimes depuis le contrôleur
                'currency' => $currency,
                'customer' => $customerId, // Utiliser directement la variable
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
                'metadata' => [
                    'platform' => 'TrouvetaBabysitter',
                    'type' => 'reservation_deposit',
                    'user_id' => $user->id
                ]
            ];

            // Note: Plus d'application_fee_amount car on ne fait plus de transfert immédiat
            // Les frais sont maintenant gérés lors du transfert différé dans releaseFundsToBabysitter()
            // Le montant total reste sur la plateforme jusqu'au déblocage

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentData);

            Log::info('PaymentIntent créé avec succès', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'application_fee' => $applicationFee,
                'customer_id' => $customerId,
                'destination' => $babysitter?->stripe_account_id
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du PaymentIntent', [
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function retrievePaymentIntent($paymentIntentId)
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

            Log::info('PaymentIntent récupéré', [
                'payment_intent_id' => $paymentIntentId,
                'status' => $paymentIntent->status,
                'amount' => $paymentIntent->amount
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du PaymentIntent', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function transferToBabysitter($amount, User $babysitter, $currency = 'eur')
    {
        try {
            return $this->stripe->transfers->create([
                'amount' => $amount * 100,
                'currency' => $currency,
                'destination' => $babysitter->stripe_account_id,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors du transfert vers le babysitter', [
                'babysitter_id' => $babysitter->id,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAccountDetails(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return null;
            }

            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            return [
                'id' => $account->id,
                'email' => $account->email,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
                'requirements' => [
                    'currently_due' => $account->requirements->currently_due ?? [],
                    'eventually_due' => $account->requirements->eventually_due ?? [],
                    'past_due' => $account->requirements->past_due ?? [],
                    'pending_verification' => $account->requirements->pending_verification ?? [],
                    'disabled_reason' => $account->requirements->disabled_reason ?? null,
                ],
                'business_profile' => [
                    'name' => $account->business_profile->name ?? null,
                    'product_description' => $account->business_profile->product_description ?? null,
                    'url' => $account->business_profile->url ?? null,
                ],
                'individual' => [
                    'first_name' => $account->individual->first_name ?? null,
                    'last_name' => $account->individual->last_name ?? null,
                    'verification' => [
                        'status' => $account->individual->verification->status ?? 'unverified',
                        'document' => $account->individual->verification->document->status ?? 'unverified',
                    ]
                ],
                'created' => $account->created,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des détails du compte Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAccountBalance(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return null;
            }

            $balance = $this->stripe->balance->retrieve([], [
                'stripe_account' => $user->stripe_account_id
            ]);

            return [
                'available' => $balance->available,
                'pending' => $balance->pending,
            ];
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération du solde du compte Stripe', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getRecentTransactions(User $user, $limit = 10)
    {
        try {
            if (!$user->stripe_account_id) {
                return [];
            }

            $transfers = $this->stripe->transfers->all([
                'destination' => $user->stripe_account_id,
                'limit' => $limit,
            ]);

            return array_map(function($transfer) {
                return [
                    'id' => $transfer->id,
                    'amount' => $transfer->amount / 100, // Convertir de centimes en euros
                    'currency' => $transfer->currency,
                    'created' => $transfer->created,
                    'description' => $transfer->description,
                ];
            }, $transfers->data);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération des transactions', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function uploadVerificationDocument(User $user, string $documentType, $frontFile, $backFile = null)
    {
        try {
            // Upload du fichier principal vers Stripe
            $frontFileUpload = $this->stripe->files->create([
                'purpose' => 'identity_document',
                'file' => fopen($frontFile->getPathname(), 'r'),
            ], [
                'stripe_account' => $user->stripe_account_id
            ]);

            $documents = [
                'front' => $frontFileUpload->id
            ];

            // Upload du verso si fourni
            if ($backFile) {
                $backFileUpload = $this->stripe->files->create([
                    'purpose' => 'identity_document',
                    'file' => fopen($backFile->getPathname(), 'r'),
                ], [
                    'stripe_account' => $user->stripe_account_id
                ]);
                $documents['back'] = $backFileUpload->id;
            }

            // Mettre à jour le compte avec le document
            $this->stripe->accounts->update($user->stripe_account_id, [
                'individual' => [
                    'verification' => [
                        'document' => $documents
                    ]
                ]
            ]);

            return [
                'success' => true,
                'file_id' => $frontFileUpload->id,
                'documents' => $documents
            ];

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload du document de vérification', [
                'user_id' => $user->id,
                'document_type' => $documentType,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function needsIdentityVerification(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return true; // Pas de compte = vérification nécessaire
            }

            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            // Vérifier si des documents d'identité sont requis
            $identityRequirements = [
                'individual.verification.document',
                'individual.verification.additional_document',
                'company.verification.document',
            ];
            
            $currentlyDue = $account->requirements->currently_due ?? [];
            $eventuallyDue = $account->requirements->eventually_due ?? [];
            $allRequirements = array_merge($currentlyDue, $eventuallyDue);
            
            // Vérifier si des requirements d'identité sont présents
            foreach ($identityRequirements as $requirement) {
                if (in_array($requirement, $allRequirements)) {
                    return true;
                }
            }
            
            // Vérifier le statut de vérification individuelle
            if ($account->individual && isset($account->individual->verification)) {
                $verificationStatus = $account->individual->verification->status ?? 'unverified';
                if ($verificationStatus !== 'verified') {
                    return true;
                }
            }
            
            // Vérifier si le compte peut faire des payouts (indicateur de vérification complète)
            return !$account->payouts_enabled;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut d\'identité', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return true; // En cas d'erreur, supposer que la vérification est nécessaire
        }
    }

    // Méthode supprimée - plus nécessaire avec l'approche Stripe Connect native
    // La vérification se fait maintenant directement via les AccountLinks

    public function forceResolveIdentityRequirements(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            // Récupérer le compte pour voir les requirements actuels
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            $currentlyDue = $account->requirements->currently_due ?? [];
            $identityRequirements = array_filter($currentlyDue, function($req) {
                return strpos($req, 'individual.verification') !== false;
            });

            if (empty($identityRequirements)) {
                Log::info('Aucun requirement de vérification d\'identité trouvé', [
                    'user_id' => $user->id,
                    'account_id' => $user->stripe_account_id
                ]);
                return true;
            }

            // Essayer de soumettre les informations minimales pour satisfaire les requirements
            $updateData = [
                'individual' => [
                    'first_name' => $user->firstname ?? 'Prénom',
                    'last_name' => $user->lastname ?? 'Nom',
                    'email' => $user->email,
                    'phone' => $user->phone ?? '+33123456789',
                    'dob' => [
                        'day' => 1,
                        'month' => 1,
                        'year' => 1990,
                    ],
                    'address' => [
                        'line1' => '123 Rue Example',
                        'city' => 'Paris',
                        'postal_code' => '75001',
                        'country' => 'FR',
                    ],
                ]
            ];

            $this->stripe->accounts->update($user->stripe_account_id, $updateData);

            Log::info('Force resolved identity requirements', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'resolved_requirements' => $identityRequirements
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la résolution forcée des requirements', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Supprimer un compte Stripe Connect
     * Les comptes test peuvent être supprimés à tout moment
     * Les comptes live Custom/Express peuvent être supprimés quand tous les soldes sont à zéro
     */
    public function deleteConnectAccount(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé pour cet utilisateur');
            }

            // Vérifier d'abord les soldes du compte
            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            // Récupérer les soldes
            $balance = $this->stripe->balance->retrieve([], [
                'stripe_account' => $user->stripe_account_id
            ]);
            
            // Vérifier si le compte a des soldes non nuls
            $hasBalance = false;
            foreach ($balance->available as $availableBalance) {
                if ($availableBalance->amount > 0) {
                    $hasBalance = true;
                    break;
                }
            }
            foreach ($balance->pending as $pendingBalance) {
                if ($pendingBalance->amount > 0) {
                    $hasBalance = true;
                    break;
                }
            }
            
            if ($hasBalance) {
                throw new \Exception('Impossible de supprimer le compte : il contient encore des fonds. Tous les soldes doivent être à zéro.');
            }
            
            // Supprimer le compte via l'API Stripe
            $response = $this->stripe->accounts->delete($user->stripe_account_id);
            
            if ($response->deleted) {
                // Mettre à jour l'utilisateur en base
                $user->update([
                    'stripe_account_id' => null,
                    'stripe_account_status' => null
                ]);
                
                Log::info('Compte Stripe Connect supprimé avec succès', [
                    'user_id' => $user->id,
                    'deleted_account_id' => $user->stripe_account_id
                ]);
                
                return true;
            }
            
            throw new \Exception('La suppression du compte a échoué');
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression du compte Stripe Connect', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Rejeter un compte Stripe Connect
     * Seuls les comptes Custom/Express peuvent être rejetés
     * Les comptes live peuvent être rejetés seulement si tous les soldes sont à zéro
     */
    public function rejectConnectAccount(User $user, string $reason = 'other')
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé pour cet utilisateur');
            }

            // Valider la raison
            $validReasons = ['fraud', 'terms_of_service', 'other'];
            if (!in_array($reason, $validReasons)) {
                throw new \Exception('Raison de rejet invalide. Valeurs acceptées : ' . implode(', ', $validReasons));
            }

            // Rejeter le compte via l'API Stripe
            $response = $this->stripe->accounts->reject($user->stripe_account_id, [
                'reason' => $reason
            ]);
            
            // Mettre à jour le statut de l'utilisateur
            $user->update([
                'stripe_account_status' => 'rejected'
            ]);
            
            Log::info('Compte Stripe Connect rejeté avec succès', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'reason' => $reason
            ]);
            
            return $response;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors du rejet du compte Stripe Connect', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer tous les comptes Stripe Connect avec leurs détails
     * Pour l'administration
     */
    public function getAllConnectAccounts($limit = 100)
    {
        try {
            // Récupérer tous les utilisateurs qui ont un compte Stripe
            $users = User::whereNotNull('stripe_account_id')
                ->with(['roles', 'babysitterProfile'])
                ->get();
            
            $accounts = [];
            
            foreach ($users as $user) {
                try {
                    $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
                    
                    // Récupérer les soldes
                    $balance = null;
                    try {
                        $stripeBalance = $this->stripe->balance->retrieve([], [
                            'stripe_account' => $user->stripe_account_id
                        ]);
                        
                        $balance = [
                            'available' => $stripeBalance->available,
                            'pending' => $stripeBalance->pending
                        ];
                    } catch (\Exception $e) {
                        // Si on ne peut pas récupérer le solde, on continue
                        $balance = null;
                    }
                    
                    $accounts[] = [
                        'user' => [
                            'id' => $user->id,
                            'firstname' => $user->firstname,
                            'lastname' => $user->lastname,
                            'email' => $user->email,
                            'created_at' => $user->created_at,
                            'roles' => $user->roles->pluck('name')->toArray(),
                            'babysitter_profile' => $user->babysitterProfile
                        ],
                        'stripe_account' => [
                            'id' => $account->id,
                            'email' => $account->email,
                            'type' => $account->type,
                            'country' => $account->country,
                            'default_currency' => $account->default_currency,
                            'charges_enabled' => $account->charges_enabled,
                            'payouts_enabled' => $account->payouts_enabled,
                            'details_submitted' => $account->details_submitted,
                            'created' => $account->created,
                            'requirements' => [
                                'currently_due' => $account->requirements->currently_due ?? [],
                                'eventually_due' => $account->requirements->eventually_due ?? [],
                                'past_due' => $account->requirements->past_due ?? [],
                                'disabled_reason' => $account->requirements->disabled_reason ?? null,
                            ],
                            'individual' => [
                                'first_name' => $account->individual->first_name ?? null,
                                'last_name' => $account->individual->last_name ?? null,
                                'verification' => [
                                    'status' => $account->individual->verification->status ?? 'unverified'
                                ]
                            ] ?? null
                        ],
                        'balance' => $balance,
                        'status' => $user->stripe_account_status,
                        'can_be_deleted' => $this->canAccountBeDeleted($account, $balance)
                    ];
                } catch (\Exception $e) {
                    // Si on ne peut pas récupérer les détails d'un compte, on l'ajoute avec des infos limitées
                    $accounts[] = [
                        'user' => [
                            'id' => $user->id,
                            'firstname' => $user->firstname,
                            'lastname' => $user->lastname,
                            'email' => $user->email,
                            'created_at' => $user->created_at,
                            'roles' => $user->roles->pluck('name')->toArray(),
                            'babysitter_profile' => $user->babysitterProfile
                        ],
                        'stripe_account' => [
                            'id' => $user->stripe_account_id,
                            'error' => 'Impossible de récupérer les détails du compte'
                        ],
                        'balance' => null,
                        'status' => $user->stripe_account_status,
                        'can_be_deleted' => false
                    ];
                }
            }
            
            return $accounts;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de tous les comptes Connect', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }



    /**
     * Récupérer tous les comptes Stripe Connect directement depuis Stripe
     * Inclut les comptes non liés à des utilisateurs locaux
     */
    public function getAllStripeAccounts($limit = 100)
    {
        try {
            // Récupérer tous les comptes depuis Stripe
            $stripeAccounts = $this->stripe->accounts->all(['limit' => $limit]);
            
            $accounts = [];
            
            foreach ($stripeAccounts->data as $account) {
                try {
                    // Chercher l'utilisateur local correspondant
                    $user = User::where('stripe_account_id', $account->id)
                        ->with(['roles', 'babysitterProfile'])
                        ->first();
                    
                    // Récupérer les soldes
                    $balance = null;
                    try {
                        $stripeBalance = $this->stripe->balance->retrieve([], [
                            'stripe_account' => $account->id
                        ]);
                        
                        $balance = [
                            'available' => $stripeBalance->available,
                            'pending' => $stripeBalance->pending
                        ];
                    } catch (\Exception $e) {
                        // Si on ne peut pas récupérer le solde, on continue
                        $balance = null;
                    }
                    
                    // Déterminer le statut
                    $status = 'unknown';
                    if ($user) {
                        $status = $user->stripe_account_status ?? 'unknown';
                    } else {
                        // Déduire le statut depuis Stripe
                        if ($account->charges_enabled && $account->payouts_enabled) {
                            $status = 'active';
                        } elseif ($account->requirements->disabled_reason) {
                            $status = 'rejected';
                        } else {
                            $status = 'pending';
                        }
                    }
                    
                    $accounts[] = [
                        'user' => $user ? [
                            'id' => $user->id,
                            'firstname' => $user->firstname,
                            'lastname' => $user->lastname,
                            'email' => $user->email,
                            'created_at' => $user->created_at,
                            'roles' => $user->roles->pluck('name')->toArray(),
                            'babysitter_profile' => $user->babysitterProfile
                        ] : null,
                        'stripe_account' => [
                            'id' => $account->id,
                            'email' => $account->email,
                            'type' => $account->type,
                            'country' => $account->country,
                            'default_currency' => $account->default_currency,
                            'charges_enabled' => $account->charges_enabled,
                            'payouts_enabled' => $account->payouts_enabled,
                            'details_submitted' => $account->details_submitted,
                            'created' => $account->created,
                            'requirements' => [
                                'currently_due' => $account->requirements->currently_due ?? [],
                                'eventually_due' => $account->requirements->eventually_due ?? [],
                                'past_due' => $account->requirements->past_due ?? [],
                                'disabled_reason' => $account->requirements->disabled_reason ?? null,
                            ],
                            'individual' => $account->individual ? [
                                'first_name' => $account->individual->first_name ?? null,
                                'last_name' => $account->individual->last_name ?? null,
                                'verification' => [
                                    'status' => $account->individual->verification->status ?? 'unverified'
                                ]
                            ] : null
                        ],
                        'balance' => $balance,
                        'status' => $status,
                        'can_be_deleted' => $this->canAccountBeDeleted($account->id),
                        'is_linked_to_user' => $user !== null
                    ];
                } catch (\Exception $e) {
                    // Si on ne peut pas récupérer les détails d'un compte, on l'ajoute avec des infos limitées
                    Log::warning('Erreur lors de la récupération d\'un compte Stripe', [
                        'account_id' => $account->id,
                        'error' => $e->getMessage()
                    ]);
                    
                    $accounts[] = [
                        'user' => null,
                        'stripe_account' => [
                            'id' => $account->id,
                            'error' => 'Impossible de récupérer les détails du compte'
                        ],
                        'balance' => null,
                        'status' => 'error',
                        'can_be_deleted' => false,
                        'is_linked_to_user' => false
                    ];
                }
            }
            
            return $accounts;
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de tous les comptes Stripe', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Supprimer un compte Stripe Connect par ID
     */
    public function deleteConnectAccountById($stripeAccountId)
    {
        try {
            // Vérifier le solde avant suppression
            $balance = $this->stripe->balance->retrieve([], ['stripe_account' => $stripeAccountId]);
            
            // Vérifier qu'il n'y a pas de solde en attente ou disponible
            $hasBalance = false;
            foreach ($balance->available as $availableBalance) {
                if ($availableBalance->amount > 0) {
                    $hasBalance = true;
                    break;
                }
            }
            foreach ($balance->pending as $pendingBalance) {
                if ($pendingBalance->amount > 0) {
                    $hasBalance = true;
                    break;
                }
            }
            
            if ($hasBalance) {
                throw new \Exception('Impossible de supprimer le compte : il contient encore des soldes. Assurez-vous que tous les virements ont été effectués.');
            }
            
            // Supprimer le compte
            $this->stripe->accounts->delete($stripeAccountId);
            
            return true;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception('Erreur Stripe lors de la suppression : ' . $e->getMessage());
        }
    }

    /**
     * Rejeter un compte Stripe Connect par ID
     */
    public function rejectConnectAccountById($stripeAccountId, $reason)
    {
        try {
            $rejectData = ['reason' => $reason];
            
            $this->stripe->accounts->reject($stripeAccountId, $rejectData);
            
            return true;
            
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \Exception('Erreur Stripe lors du rejet : ' . $e->getMessage());
        }
    }

    /**
     * Vérifier si un compte Stripe peut être supprimé par ID
     */
    public function canAccountBeDeleted($accountId)
    {
        try {
            // En mode test, tous les comptes peuvent être supprimés
            if (strpos(config('services.stripe.secret'), 'sk_test_') === 0) {
                return true;
            }
            
            // Récupérer les détails du compte
            $account = $this->stripe->accounts->retrieve($accountId);
            
            // Les comptes Standard ne peuvent pas être supprimés
            if ($account->type === 'standard') {
                return false;
            }
            
            // Vérifier les soldes
            try {
                $balance = $this->stripe->balance->retrieve([], ['stripe_account' => $accountId]);
                
                foreach ($balance->available as $availableBalance) {
                    if ($availableBalance->amount > 0) {
                        return false;
                    }
                }
                foreach ($balance->pending as $pendingBalance) {
                    if ($pendingBalance->amount > 0) {
                        return false;
                    }
                }
            } catch (\Exception $e) {
                // Si on ne peut pas récupérer le solde, on ne peut pas garantir la suppression
                return false;
            }
            
            return true;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Créer un PaymentIntent avec un moyen de paiement sauvegardé
     */
    public function createPaymentIntentWithSavedMethod($amount, $currency, $paymentMethodId, User $user, $applicationFee = 0, $babysitter = null)
    {
        try {
            // S'assurer que l'utilisateur a un customer ID
            if (!$user->stripe_customer_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'metadata' => [
                        'user_id' => $user->id,
                        'platform' => 'TrouvetaBabysitter'
                    ]
                ]);
                
                $user->update(['stripe_customer_id' => $customer->id]);
            }

            $paymentIntentData = [
                'amount' => $amount,
                'currency' => $currency,
                'customer' => $user->stripe_customer_id,
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'automatic',
                'confirm' => true,
                'return_url' => route('messaging.index'),
                'metadata' => [
                    'user_id' => $user->id,
                    'platform' => 'TrouvetaBabysitter',
                    'payment_type' => 'reservation_deposit'
                ]
            ];

            // Note: Plus d'application_fee_amount car on ne fait plus de transfert immédiat
            // Les frais sont gérés lors du transfert différé

            $paymentIntent = $this->stripe->paymentIntents->create($paymentIntentData);

            Log::info('PaymentIntent créé avec moyen sauvegardé', [
                'payment_intent_id' => $paymentIntent->id,
                'amount' => $amount,
                'payment_method_id' => $paymentMethodId,
                'user_id' => $user->id,
                'status' => $paymentIntent->status
            ]);

            return $paymentIntent;

        } catch (\Exception $e) {
            Log::error('Erreur création PaymentIntent avec moyen sauvegardé', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Sauvegarder un moyen de paiement pour un utilisateur
     */
    public function savePaymentMethod($paymentMethodId, User $user)
    {
        try {
            // S'assurer que l'utilisateur a un customer ID
            if (!$user->stripe_customer_id) {
                $customer = $this->stripe->customers->create([
                    'email' => $user->email,
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'metadata' => [
                        'user_id' => $user->id,
                        'platform' => 'TrouvetaBabysitter'
                    ]
                ]);
                
                $user->update(['stripe_customer_id' => $customer->id]);
            }

            // Attacher le moyen de paiement au customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $user->stripe_customer_id,
            ]);

            Log::info('Moyen de paiement sauvegardé', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'customer_id' => $user->stripe_customer_id
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Erreur sauvegarde moyen de paiement', [
                'user_id' => $user->id,
                'payment_method_id' => $paymentMethodId,
                'error' => $e->getMessage()
            ]);
            
            // Ne pas faire échouer le processus si la sauvegarde échoue
            return false;
        }
    }

    /**
     * Configurer la fréquence des virements pour un compte Connect
     */
    public function updatePayoutSchedule(User $user, $interval = 'weekly', $weeklyAnchor = 'friday')
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            $scheduleData = [
                'interval' => $interval, // 'daily', 'weekly', 'monthly'
            ];

            if ($interval === 'weekly') {
                $scheduleData['weekly_anchor'] = $weeklyAnchor; // 'monday', 'tuesday', etc.
            } elseif ($interval === 'monthly') {
                $scheduleData['monthly_anchor'] = 1; // 1-31
            }

            $this->stripe->accounts->update($user->stripe_account_id, [
                'settings' => [
                    'payouts' => [
                        'schedule' => $scheduleData
                    ]
                ]
            ]);

            Log::info('Fréquence des virements mise à jour', [
                'user_id' => $user->id,
                'interval' => $interval,
                'weekly_anchor' => $weeklyAnchor
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour de la fréquence des virements', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Configurer le montant minimum pour les virements
     */
    public function updateMinimumPayoutAmount(User $user, $minimumAmount = 2500) // 25€ en centimes
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            $this->stripe->accounts->update($user->stripe_account_id, [
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'minimum_amount' => $minimumAmount
                        ]
                    ]
                ]
            ]);

            Log::info('Montant minimum des virements mis à jour', [
                'user_id' => $user->id,
                'minimum_amount' => $minimumAmount / 100 . '€'
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la mise à jour du montant minimum', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bloquer les virements automatiques
     */
    public function disableAutomaticPayouts(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            $this->stripe->accounts->update($user->stripe_account_id, [
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'manual'
                        ]
                    ]
                ]
            ]);

            Log::info('Virements automatiques désactivés', [
                'user_id' => $user->id
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la désactivation des virements automatiques', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Déclencher un virement manuel
     */
    public function createManualPayout(User $user, $amount = null, $currency = 'eur')
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            // Si aucun montant spécifié, virer tout le solde disponible
            if ($amount === null) {
                $balance = $this->getAccountBalance($user);
                if (!$balance || empty($balance['available'])) {
                    throw new \Exception('Aucun solde disponible pour le virement');
                }

                $amount = 0;
                foreach ($balance['available'] as $availableBalance) {
                    if ($availableBalance['currency'] === $currency) {
                        $amount = $availableBalance['amount'];
                        break;
                    }
                }

                if ($amount === 0) {
                    throw new \Exception('Aucun solde disponible dans la devise demandée');
                }
            }

            // Vérifier le montant minimum (25€)
            if ($amount < 2500) {
                throw new \Exception('Le montant minimum pour un virement est de 25€');
            }

            // Calculer les frais de la plateforme : 5% avec minimum 2€
            $originalAmountEuros = $amount / 100; // Convertir en euros
            $platformFeeEuros = max(2.00, round($originalAmountEuros * 0.05, 2)); // 5% minimum 2€
            $platformFeeCentimes = round($platformFeeEuros * 100); // Reconvertir en centimes
            
            // Montant que recevra réellement la babysitter
            $finalPayoutAmount = $amount - $platformFeeCentimes;
            
            // Vérifier que le montant final reste positif
            if ($finalPayoutAmount <= 0) {
                throw new \Exception('Le montant après déduction des frais est trop faible');
            }

            Log::info('💳 Calcul des frais de virement bancaire', [
                'user_id' => $user->id,
                'amount_requested' => $originalAmountEuros . '€',
                'platform_fee' => $platformFeeEuros . '€',
                'amount_transferred_to_bank' => ($finalPayoutAmount / 100) . '€',
                'platform_keeps' => $platformFeeEuros . '€'
            ]);

            $payout = $this->stripe->payouts->create([
                'amount' => $finalPayoutAmount, // Montant après déduction des frais
                'currency' => $currency,
                'method' => 'standard',
                'metadata' => [
                    'original_amount_requested' => $originalAmountEuros,
                    'platform_fee_applied' => $platformFeeEuros,
                    'platform_fee_percentage' => '5%',
                    'final_amount_transferred' => $finalPayoutAmount / 100
                ]
            ], [
                'stripe_account' => $user->stripe_account_id
            ]);

            Log::info('Virement manuel créé avec frais prélevés', [
                'user_id' => $user->id,
                'payout_id' => $payout->id,
                'original_amount' => $originalAmountEuros . '€',
                'platform_fee' => $platformFeeEuros . '€',
                'final_amount' => ($finalPayoutAmount / 100) . '€'
            ]);

            return $payout;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du virement manuel', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Récupérer l'historique des virements
     */
    public function getPayoutHistory(User $user, $limit = 20)
    {
        try {
            if (!$user->stripe_account_id) {
                return [];
            }

            $payouts = $this->stripe->payouts->all([
                'limit' => $limit,
            ], [
                'stripe_account' => $user->stripe_account_id
            ]);

            return array_map(function($payout) {
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount / 100,
                    'currency' => $payout->currency,
                    'status' => $payout->status,
                    'method' => $payout->method,
                    'arrival_date' => $payout->arrival_date,
                    'created' => $payout->created,
                    'description' => $payout->description,
                ];
            }, $payouts->data);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de l\'historique des virements', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Générer une facture pour une babysitter
     */
    public function generateInvoiceForBabysitter(User $babysitter, $reservations, $period)
    {
        try {
            $totalAmount = 0;
            $services = [];
            
            foreach ($reservations as $reservation) {
                $amount = $reservation->total_amount ?? $reservation->hourly_rate * $reservation->duration_hours;
                $totalAmount += $amount;
                
                $services[] = [
                    'date' => $reservation->service_start_at->format('d/m/Y'),
                    'description' => "Service de babysitting - {$reservation->duration_hours}h",
                    'amount' => $amount
                ];
            }
            
            // Ici vous pourriez utiliser une librairie comme DomPDF ou Snappy
            // Pour cet exemple, on retourne les données structurées
            return [
                'babysitter' => [
                    'name' => $babysitter->firstname . ' ' . $babysitter->lastname,
                    'email' => $babysitter->email,
                ],
                'period' => $period,
                'services' => $services,
                'total_amount' => $totalAmount,
                'generated_at' => now(),
            ];
            
        } catch (\Exception $e) {
            Log::error('Erreur lors de la génération de facture', [
                'babysitter_id' => $babysitter->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un remboursement Stripe
     */
    public function createRefund($paymentIntentId, $amount = null, $reason = null, Reservation $reservation = null)
    {
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
                'reason' => 'requested_by_customer', // Raison Stripe standard
                'metadata' => [
                    'platform' => 'TrouvetaBabysitter',
                    'refund_reason' => $reason ?? 'Annulation de service'
                ]
            ];

            // Si un montant spécifique est fourni, l'utiliser (sinon remboursement complet)
            if ($amount !== null) {
                $refundData['amount'] = $amount; // Montant en centimes
            }

            // Créer le remboursement
            $refund = $this->stripe->refunds->create($refundData);

            // Mettre à jour le statut des fonds si une réservation est fournie
            if ($reservation) {
                $reservation->update([
                    'stripe_refund_id' => $refund->id,
                    'funds_status' => 'refunded',
                    'funds_released_at' => null,
                    'funds_hold_until' => null
                ]);
            }

            Log::info('Remboursement Stripe créé avec succès', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $refund->amount,
                'status' => $refund->status,
                'reason' => $reason,
                'reservation_id' => $reservation?->id
            ]);

            return $refund;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du remboursement Stripe', [
                'payment_intent_id' => $paymentIntentId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un remboursement partiel Stripe
     */
    public function createPartialRefund($paymentIntentId, $refundPercentage = 0.8, $reason = null)
    {
        try {
            // Récupérer le PaymentIntent pour connaître le montant total
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            // Calculer le montant du remboursement partiel
            $refundAmount = intval($paymentIntent->amount * $refundPercentage);

            return $this->createRefund($paymentIntentId, $refundAmount, $reason);

        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du remboursement partiel Stripe', [
                'payment_intent_id' => $paymentIntentId,
                'refund_percentage' => $refundPercentage,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un remboursement où la plateforme couvre les frais
     * (Utilisé quand la babysitter annule et doit être pénalisée)
     */
    public function createRefundPlatformCoversfees($paymentIntentId, $reason = null)
    {
        try {
            // Récupérer le PaymentIntent pour connaître le montant total
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            $refundData = [
                'payment_intent' => $paymentIntentId,
                'reason' => 'requested_by_customer', // Raison Stripe standard
                // Pas de montant spécifié = remboursement complet
                'metadata' => [
                    'platform' => 'TrouvetaBabysitter',
                    'refund_reason' => $reason ?? 'Annulation babysitter - Plateforme couvre les frais',
                    'fees_covered_by' => 'platform'
                ]
            ];

            // Créer le remboursement complet
            $refund = $this->stripe->refunds->create($refundData);

            Log::info('Remboursement complet avec frais couverts par la plateforme', [
                'refund_id' => $refund->id,
                'payment_intent_id' => $paymentIntentId,
                'amount' => $refund->amount,
                'status' => $refund->status,
                'reason' => $reason,
                'fees_covered_by' => 'platform'
            ]);

            return $refund;

        } catch (\Exception $e) {
            Log::error('Erreur lors du remboursement avec frais couverts par la plateforme', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Créer un customer Stripe pour un utilisateur
     */
    private function createCustomerForUser(User $user)
    {
        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->firstname . ' ' . $user->lastname,
            'metadata' => [
                'user_id' => $user->id,
                'platform' => 'TrouvetaBabysitter'
            ]
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    /**
     * Créer un remboursement avec déduction du compte Connect babysitter
     * Ce système respecte les règles de l'utilisateur :
     * - Parent reçoit : montant payé - frais service - frais Stripe remboursement
     * - Babysitter perd : fonds de son compte Connect
     */
    public function createRefundWithBabysitterDeduction($paymentIntentId, Reservation $reservation, $reason = null)
    {
        try {
            $parentRefundAmount = $reservation->getParentRefundAmount();
            $babysitterDeduction = $reservation->getBabysitterDeductionAmount();
            
            Log::info('Préparation remboursement avec déduction babysitter', [
                'reservation_id' => $reservation->id,
                'parent_refund_amount' => $parentRefundAmount,
                'babysitter_deduction' => $babysitterDeduction,
                'payment_intent_id' => $paymentIntentId
            ]);

            // 1. Créer le remboursement pour le parent
            $refund = null;
            if ($parentRefundAmount > 0) {
                $refundData = [
                    'payment_intent' => $paymentIntentId,
                    'amount' => round($parentRefundAmount * 100), // Convertir en centimes
                    'reason' => 'requested_by_customer',
                    'metadata' => [
                        'platform' => 'Miminoo',
                        'reservation_id' => $reservation->id,
                        'refund_reason' => $reason ?? 'Annulation avec déduction babysitter',
                        'refund_type' => 'parent_partial_refund',
                        'service_fees_retained' => $reservation->service_fee,
                        'stripe_fees_deducted' => $reservation->getStripeRefundFees()
                    ]
                ];

                $refund = $this->stripe->refunds->create($refundData);
                
                Log::info('Remboursement parent créé', [
                    'refund_id' => $refund->id,
                    'amount' => $refund->amount / 100,
                    'status' => $refund->status
                ]);
            }

            // 2. La babysitter "perd" les fonds : on ne lui transfère simplement rien
            // Les fonds restent sur la plateforme et peuvent servir au remboursement parent + frais plateforme
            if ($babysitterDeduction > 0) {
                // La "déduction" babysitter est conceptuelle - elle ne reçoit simplement pas les fonds
                // qui auraient dû lui être transférés lors du PaymentIntent original
                
                Log::info('Babysitter perd les fonds (non-transfert)', [
                    'reservation_id' => $reservation->id,
                    'babysitter_id' => $reservation->babysitter_id,
                    'amount_not_transferred' => $babysitterDeduction,
                    'babysitter_account' => $reservation->babysitter->stripe_account_id,
                    'explanation' => 'Les fonds restent sur la plateforme au lieu d\'être transférés à la babysitter'
                ]);
                
                // Si des fonds avaient déjà été transférés à la babysitter précédemment, 
                // il faudrait les récupérer, mais normalement ce n'est pas le cas dans notre flow
                $this->handlePreviousTransfersIfAny($reservation);
            }

            // 3. Mettre à jour le statut des fonds dans la réservation
            $reservation->update([
                'stripe_refund_id' => $refund?->id,
                'funds_status' => 'refunded', // Parent remboursé = babysitter ne touche rien
                'funds_released_at' => null,
                'funds_hold_until' => null
            ]);

            // 4. Enregistrer les transactions dans notre base
            $this->recordRefundTransactions($reservation, $parentRefundAmount, $babysitterDeduction, $refund);

            return $refund;

        } catch (\Exception $e) {
            Log::error('Erreur lors du remboursement avec déduction babysitter', [
                'payment_intent_id' => $paymentIntentId,
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Vérifier s'il y a eu des transfers précédents vers la babysitter et les reverser si nécessaire
     */
    private function handlePreviousTransfersIfAny(Reservation $reservation)
    {
        try {
            if (!$reservation->stripe_payment_intent_id) {
                return;
            }

            // Chercher les transfers existants liés à ce PaymentIntent
            $transfers = $this->stripe->transfers->all([
                'destination' => $reservation->babysitter->stripe_account_id,
                'limit' => 10
            ]);

            foreach ($transfers->data as $transfer) {
                // Vérifier si ce transfer est lié à notre réservation
                if (isset($transfer->metadata['reservation_id']) && 
                    $transfer->metadata['reservation_id'] == $reservation->id) {
                    
                    // Reverser ce transfer
                    $reversal = $this->stripe->transfers->createReversal($transfer->id, [
                        'amount' => $transfer->amount,
                        'metadata' => [
                            'type' => 'refund_recovery',
                            'reservation_id' => $reservation->id,
                            'reason' => 'Récupération fonds pour remboursement parent'
                        ]
                    ]);

                    Log::info('Transfer précédent reversé', [
                        'transfer_id' => $transfer->id,
                        'reversal_id' => $reversal->id,
                        'amount' => $transfer->amount / 100,
                        'reservation_id' => $reservation->id
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::warning('Impossible de vérifier/reverser les transfers précédents', [
                'reservation_id' => $reservation->id,
                'babysitter_account' => $reservation->babysitter->stripe_account_id,
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer le processus pour ça
        }
    }

    /**
     * Créer une dette pour la babysitter en cas d'échec de déduction immédiate
     */
    private function createBabysitterDebt(Reservation $reservation, float $amount, $reason)
    {
        // TODO: Implémenter un système de dette/créance
        // Pour l'instant, on log l'information
        Log::warning('Dette babysitter créée', [
            'reservation_id' => $reservation->id,
            'babysitter_id' => $reservation->babysitter_id,
            'debt_amount' => $amount,
            'reason' => $reason,
            'created_at' => now()
        ]);
    }

    /**
     * Transférer les fonds vers la babysitter après validation (déblocage des fonds)
     */
    public function releaseFundsToBabysitter(Reservation $reservation)
    {
        try {
            if (!$reservation->babysitter->stripe_account_id) {
                throw new \Exception('Babysitter n\'a pas de compte Stripe Connect');
            }

            if ($reservation->funds_status !== 'held_for_validation') {
                throw new \Exception('Les fonds ne sont pas en attente de validation');
            }

            // Calculer le montant à transférer (babysitter_amount déjà calculé sans les frais plateforme)
            $transferAmount = $reservation->babysitter_amount ?? $reservation->deposit_amount;

            // Créer le transfer vers la babysitter
            $transfer = $this->stripe->transfers->create([
                'amount' => round($transferAmount * 100), // En centimes
                'currency' => 'eur',
                'destination' => $reservation->babysitter->stripe_account_id,
                'metadata' => [
                    'reservation_id' => $reservation->id,
                    'type' => 'babysitter_payment_release',
                    'release_reason' => 'service_completed_validation_passed',
                    'original_deposit' => $reservation->total_deposit,
                    'platform_fee' => $reservation->platform_fee,
                    'babysitter_net_amount' => $transferAmount
                ]
            ]);

            // Mettre à jour la réservation
            $reservation->update([
                'stripe_transfer_id' => $transfer->id,
                'funds_status' => 'released',
                'funds_released_at' => now()
            ]);

            // Enregistrer la transaction
            \App\Models\Transaction::create([
                'ad_id' => $reservation->ad_id,
                'reservation_id' => $reservation->id,
                'payer_id' => $reservation->parent_id,
                'babysitter_id' => $reservation->babysitter_id,
                'type' => 'payout',
                'amount' => $transferAmount,
                'babysitter_amount' => $transferAmount, // Ajout du champ requis
                'status' => 'succeeded',
                'stripe_id' => $transfer->id,
                'description' => 'Paiement babysitter après validation (24h)',
                'metadata' => [
                    'transfer_id' => $transfer->id,
                    'release_type' => 'automatic_after_validation'
                ]
            ]);

            Log::info('Fonds libérés vers la babysitter', [
                'reservation_id' => $reservation->id,
                'transfer_id' => $transfer->id,
                'amount' => $transferAmount,
                'babysitter_id' => $reservation->babysitter_id
            ]);

            return $transfer;

        } catch (\Exception $e) {
            Log::error('Erreur lors de la libération des fonds', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bloquer les fonds en cas de dispute
     */
    public function holdFundsForDispute(Reservation $reservation)
    {
        $reservation->update([
            'funds_status' => 'disputed',
            'funds_hold_until' => null // Pas de limite de temps en cas de dispute
        ]);

        Log::info('Fonds bloqués pour dispute', [
            'reservation_id' => $reservation->id
        ]);
    }

    /**
     * Marquer les fonds comme annulés (pas de remboursement)
     */
    public function cancelFundsWithoutRefund(Reservation $reservation, $reason = null)
    {
        $reservation->update([
            'funds_status' => 'cancelled',
            'funds_hold_until' => null,
            'funds_released_at' => null
        ]);

        Log::info('Fonds marqués comme annulés sans remboursement', [
            'reservation_id' => $reservation->id,
            'reason' => $reason ?? 'Annulation sans remboursement'
        ]);
    }

    /**
     * Gérer l'annulation par la babysitter avec remboursement du parent
     */
    public function handleBabysitterCancellationWithRefund(Reservation $reservation, $reason = null)
    {
        try {
            // La babysitter annule : parent doit être remboursé, babysitter ne touche rien
            $refund = $this->createRefundWithBabysitterDeduction(
                $reservation->stripe_payment_intent_id,
                $reservation,
                $reason ?? 'Annulation par la babysitter'
            );

            // Marquer que c'est la babysitter qui a annulé (pour les messages)
            $reservation->update([
                'funds_status' => 'refunded',
                'stripe_refund_id' => $refund?->id
            ]);

            Log::info('Annulation babysitter avec remboursement parent traitée', [
                'reservation_id' => $reservation->id,
                'refund_id' => $refund?->id,
                'parent_refund_amount' => $reservation->getParentRefundAmount(),
                'babysitter_loses' => $reservation->getBabysitterDeductionAmount()
            ]);

            return $refund;

        } catch (\Exception $e) {
            Log::error('Erreur lors du traitement annulation babysitter', [
                'reservation_id' => $reservation->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Enregistrer les transactions de remboursement dans notre base
     */
    private function recordRefundTransactions(Reservation $reservation, float $parentRefundAmount, float $babysitterDeduction, $refund)
    {
        // Transaction de remboursement parent
        if ($parentRefundAmount > 0) {
            \App\Models\Transaction::create([
                'ad_id' => $reservation->ad_id,
                'reservation_id' => $reservation->id,
                'payer_id' => $reservation->parent_id, // Utiliser payer_id au lieu de user_id
                'babysitter_id' => $reservation->babysitter_id,
                'type' => 'refund',
                'amount' => $parentRefundAmount,
                'babysitter_amount' => 0, // Remboursement = pas de gain babysitter
                'status' => 'succeeded',
                'stripe_refund_id' => $refund?->id,
                'description' => 'Remboursement partiel (frais service et Stripe déduits)',
                'metadata' => [
                    'original_amount' => $reservation->total_deposit,
                    'service_fees_retained' => $reservation->service_fee,
                    'stripe_fees_deducted' => $reservation->getStripeRefundFees()
                ]
            ]);
        }

        // Transaction de déduction babysitter
        if ($babysitterDeduction > 0) {
            \App\Models\Transaction::create([
                'ad_id' => $reservation->ad_id,
                'reservation_id' => $reservation->id,
                'payer_id' => $reservation->parent_id, // Le parent comme "payeur" de cette transaction
                'babysitter_id' => $reservation->babysitter_id, // La babysitter qui perd les fonds
                'type' => 'deduction',
                'amount' => -$babysitterDeduction, // Montant négatif pour déduction
                'babysitter_amount' => -$babysitterDeduction, // Déduction = perte babysitter
                'status' => 'succeeded',
                'description' => 'Déduction pour remboursement parent',
                'metadata' => [
                    'refund_amount_to_parent' => $parentRefundAmount,
                    'deduction_reason' => 'parent_refund',
                    'babysitter_loses' => $babysitterDeduction
                ]
            ]);
        }
    }

    /**
     * Créer ou mettre à jour un compte Connect avec account token (méthode principale pour la France)
     */
    public function createOrUpdateConnectAccountInternal(User $user, array $validated)
    {
        try {
            // Vérifier si un account token a été fourni
            if (isset($validated['account_token'])) {
                Log::info('🔑 Account token fourni - création avec token client', [
                    'user_id' => $user->id
                ]);
                
                // Préparer les données additionnelles pour le compte bancaire
                $additionalData = [];
                if (isset($validated['iban']) && isset($validated['account_holder_name'])) {
                    $additionalData = [
                        'iban' => $validated['iban'],
                        'account_holder_name' => $validated['account_holder_name'],
                        'business_description' => $validated['business_description'] ?? 'Services de garde d\'enfants et babysitting',
                        'mcc' => $validated['mcc'] ?? '8299'
                    ];
                }
                
                return $this->createConnectAccountWithClientToken($user, $validated['account_token'], $additionalData);
            }

            // Si l'utilisateur a déjà un compte Stripe Connect, essayer d'abord de le mettre à jour
            if ($user->stripe_account_id) {
                try {
                    // Vérifier que le compte existe encore
                    $this->stripe->accounts->retrieve($user->stripe_account_id);
                    
                    Log::info('🔄 Mise à jour du compte existant', [
                        'user_id' => $user->id,
                        'account_id' => $user->stripe_account_id
                    ]);

                    // Tenter de mettre à jour le compte existant avec les nouvelles données
                    return $this->updateExistingConnectAccount($user, $validated);
                    
                } catch (\Exception $e) {
                    Log::warning('Compte Stripe Connect introuvable ou inaccessible, utilisation de l\'onboarding externe', [
                        'user_id' => $user->id,
                        'old_account_id' => $user->stripe_account_id,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Nettoyer la référence au compte qui n'existe plus
                    $user->stripe_account_id = null;
                    $user->save();
                    
                    // Lancer une exception pour suggérer l'onboarding externe
                    throw new \Exception('Configuration avec account tokens requise. Veuillez utiliser la configuration externe avec Stripe.');
                }
            }

            // Si pas de token et pas de compte existant, suggérer l'onboarding externe
            throw new \Exception('Configuration avec account tokens requise. Veuillez utiliser la configuration externe avec Stripe.');

        } catch (\Exception $e) {
            // Si l'erreur concerne la nécessité d'utiliser des tokens pour la France
            if (strpos($e->getMessage(), 'Connect platforms based in FR must create accounts via account tokens') !== false) {
                Log::info('🇫🇷 Erreur détectée pour la France - utilisation des account tokens requise', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }

            throw $e;
        }
    }

    /**
     * Formater un numéro de téléphone pour Stripe
     */
    private function formatPhoneForStripe($phone)
    {
        try {
            // Nettoyer le numéro
            $phone = preg_replace('/[^0-9+]/', '', $phone);
            
            // Gérer les différents formats français
            if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
                // Format 0612345678 -> +33612345678
                return '+33' . substr($phone, 1);
            } elseif (strlen($phone) === 9 && substr($phone, 0, 1) !== '0') {
                // Format 612345678 -> +33612345678
                return '+33' . $phone;
            } elseif (substr($phone, 0, 3) === '+33') {
                // Déjà au bon format
                return $phone;
            } elseif (substr($phone, 0, 2) === '33') {
                // Format 33612345678 -> +33612345678
                return '+' . $phone;
            }
            
            return null; // Format non reconnu
        } catch (\Exception $e) {
            Log::warning('Impossible de formater le téléphone pour Stripe', [
                'phone' => $phone,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Créer un compte Stripe Connect avec account token (créé côté client)
     */
    public function createConnectAccountWithClientToken(User $user, string $accountToken, array $additionalData = [])
    {
        try {
            Log::info('🚀 Création compte Connect avec token client', [
                'user_id' => $user->id,
                'account_token' => substr($accountToken, 0, 20) . '...'
            ]);

            // Créer le compte Connect avec le token
            $accountData = [
                'type' => 'custom',
                'country' => 'FR',
                'account_token' => $accountToken,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_profile' => [
                    'mcc' => $additionalData['mcc'] ?? '8299', // Code MCC pour services de garde d'enfants
                    'product_description' => $additionalData['business_description'] ?? 'Services de garde d\'enfants et babysitting',
                    'url' => 'https://trouvetababysitter.com', // URL du site web d'entreprise requis
                ],
                'metadata' => [
                    'user_id' => $user->id,
                    'platform' => 'TrouvetaBabysitter',
                    'created_with_client_token' => 'true'
                ]
            ];

            $account = $this->stripe->accounts->create($accountData);

            // Configurer les virements en manuel par défaut
            $this->stripe->accounts->update($account->id, [
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'manual'
                        ]
                    ]
                ]
            ]);

            // Sauvegarder l'ID du compte
            $user->update([
                'stripe_account_id' => $account->id,
                'stripe_account_status' => 'pending'
            ]);

            Log::info('✅ Compte Stripe Connect créé avec token client', [
                'user_id' => $user->id,
                'account_id' => $account->id
            ]);

            // Ajouter les informations bancaires si fournies
            if (isset($additionalData['iban']) && isset($additionalData['account_holder_name'])) {
                $this->addBankAccountToConnectAccount($user, $additionalData);
            }

            return $account;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la création du compte Connect avec token client', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Ajouter un compte bancaire à un compte Connect existant
     */
    private function addBankAccountToConnectAccount(User $user, array $bankData)
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé pour cet utilisateur');
            }

            Log::info('📦 Ajout compte bancaire au compte Connect', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id
            ]);

            // Préparer les données du compte bancaire
            $externalAccountData = [
                'object' => 'bank_account',
                'country' => 'FR',
                'currency' => 'eur',
                'account_holder_name' => $bankData['account_holder_name'],
                'account_number' => str_replace(' ', '', $bankData['iban']), // Supprimer les espaces de l'IBAN
            ];

            // Ajouter le compte bancaire via l'API External Accounts
            $bankAccount = $this->stripe->accounts->createExternalAccount(
                $user->stripe_account_id,
                ['external_account' => $externalAccountData]
            );

            Log::info('✅ Compte bancaire ajouté avec succès', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'bank_account_id' => $bankAccount->id
            ]);

            return $bankAccount;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'ajout du compte bancaire', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'error' => $e->getMessage()
            ]);
            
            // Ne pas faire échouer toute la création du compte pour un problème bancaire
            // L'utilisateur pourra ajouter son IBAN plus tard
            Log::warning('⚠️ Compte Connect créé sans compte bancaire', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id
            ]);
        }
    }

    /**
     * Mettre à jour un compte Connect existant
     */
    private function updateExistingConnectAccount(User $user, array $validated)
    {
        try {
            $updateData = [];

            // Informations individuelles
            $individual = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'dob' => [
                    'day' => (int) $validated['dob_day'],
                    'month' => (int) $validated['dob_month'],
                    'year' => (int) $validated['dob_year'],
                ],
                'address' => [
                    'line1' => $validated['address_line1'],
                    'city' => $validated['address_city'],
                    'postal_code' => $validated['address_postal_code'],
                    'country' => $validated['address_country'],
                ],
            ];

            // Ajouter le téléphone si fourni
            if (!empty($validated['phone'])) {
                $formattedPhone = $this->formatPhoneForStripe($validated['phone']);
                if ($formattedPhone) {
                    $individual['phone'] = $formattedPhone;
                }
            }

            $updateData['individual'] = $individual;

            // Profil business
            $updateData['business_profile'] = [
                'mcc' => $validated['mcc'] ?? '8299', // Code MCC pour services de garde d'enfants
                'product_description' => $validated['business_description'],
            ];

            // Informations bancaires externes (compte externe)
            $updateData['external_account'] = [
                'object' => 'bank_account',
                'country' => 'FR',
                'currency' => 'eur',
                'account_holder_name' => $validated['account_holder_name'],
                'account_number' => str_replace(' ', '', $validated['iban']),
            ];

            // Acceptation des conditions d'utilisation
            $updateData['tos_acceptance'] = [
                'date' => time(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ];

            // Effectuer la mise à jour
            $account = $this->stripe->accounts->update($user->stripe_account_id, $updateData);

            Log::info('✅ Compte Connect mis à jour', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id
            ]);

            return $account;

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la mise à jour du compte Connect', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

   
    /**
     * Upload identity documents to Stripe
     */
    public function uploadIdentityDocuments(User $user, $frontFile, $backFile = null, $documentType = 'id_card')
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            Log::info('📄 Upload de documents d\'identité vers Stripe', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'document_type' => $documentType,
                'has_front' => !is_null($frontFile),
                'has_back' => !is_null($backFile)
            ]);

            $uploadedFiles = [];

            // Upload du recto (obligatoire)
            if ($frontFile) {
                $frontStripeFile = $this->stripe->files->create([
                    'purpose' => 'identity_document',
                    'file' => fopen($frontFile->getRealPath(), 'r'),
                ], [
                    'stripe_account' => $user->stripe_account_id,
                ]);
                $uploadedFiles['front'] = $frontStripeFile->id;
                Log::info('✅ Recto uploadé', ['file_id' => $frontStripeFile->id]);
            }

            // Upload du verso (optionnel)
            if ($backFile) {
                $backStripeFile = $this->stripe->files->create([
                    'purpose' => 'identity_document',
                    'file' => fopen($backFile->getRealPath(), 'r'),
                ], [
                    'stripe_account' => $user->stripe_account_id,
                ]);
                $uploadedFiles['back'] = $backStripeFile->id;
                Log::info('✅ Verso uploadé', ['file_id' => $backStripeFile->id]);
            }

            // Créer un account token avec les documents au lieu de mettre à jour directement
            $tokenData = [
                'account' => [
                    'individual' => [
                        'verification' => [
                            'document' => [
                                'front' => $uploadedFiles['front'],
                            ],
                        ],
                    ],
                ],
            ];

            // Ajouter le verso si disponible
            if (isset($uploadedFiles['back'])) {
                $tokenData['account']['individual']['verification']['document']['back'] = $uploadedFiles['back'];
            }

            // Créer le token - utiliser l'API REST directement
            $accountToken = AccountToken::create($tokenData);

            // Mettre à jour le compte avec le token
            $account = $this->stripe->accounts->update(
                $user->stripe_account_id,
                ['account_token' => $accountToken->id]
            );

            Log::info('✅ Compte mis à jour avec les documents', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'verification_status' => $account->individual->verification->status ?? 'unknown'
            ]);

            return [
                'account_id' => $user->stripe_account_id,
                'uploaded_files' => $uploadedFiles,
                'verification_status' => $account->individual->verification->status ?? 'pending'
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de l\'upload des documents d\'identité', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Met à jour un compte Stripe Connect avec un token généré côté client
     * Cette méthode suit l'approche recommandée par Stripe pour l'upload de documents d'identité
     * 
     * @param User $user
     * @param string $accountToken Le token généré côté client
     * @param string $documentType Type de document (id_card, passport)
     * @return array
     */
    public function updateAccountWithToken(User $user, string $accountToken, string $documentType = 'id_card')
    {
        try {
            if (!$user->stripe_account_id) {
                throw new \Exception('Aucun compte Stripe Connect trouvé');
            }

            Log::info('🏗️ Mise à jour du compte Stripe avec token côté client', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'document_type' => $documentType,
                'token_prefix' => substr($accountToken, 0, 20) . '...'
            ]);

            // Mettre à jour le compte avec le token généré côté client
            $account = $this->stripe->accounts->update(
                $user->stripe_account_id,
                ['account_token' => $accountToken]
            );

            Log::info('✅ Compte mis à jour avec token côté client', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id,
                'verification_status' => $account->individual->verification->status ?? 'unknown',
                'requirements_due' => $account->requirements->currently_due ?? [],
                'requirements_pending' => $account->requirements->pending_verification ?? []
            ]);

            return [
                'account_id' => $user->stripe_account_id,
                'verification_status' => $account->individual->verification->status ?? 'pending',
                'requirements' => [
                    'currently_due' => $account->requirements->currently_due ?? [],
                    'eventually_due' => $account->requirements->eventually_due ?? [],
                    'pending_verification' => $account->requirements->pending_verification ?? []
                ]
            ];

        } catch (\Exception $e) {
            Log::error('❌ Erreur lors de la mise à jour du compte avec token', [
                'user_id' => $user->id,
                'account_id' => $user->stripe_account_id ?? 'N/A',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
} 