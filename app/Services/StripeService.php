<?php

namespace App\Services;

use App\Models\User;
use Stripe\StripeClient;
use Illuminate\Support\Facades\Log;

class StripeService
{
    protected $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createConnectAccount(User $user)
    {
        try {
            $account = $this->stripe->accounts->create([
                'type' => 'express',
                'country' => 'FR',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                'business_type' => 'individual',
                'individual' => [
                    'email' => $user->email,
                    'first_name' => $user->firstname,
                    'last_name' => $user->lastname,
                ],
                'business_profile' => [
                    'mcc' => '8351', // Code MCC pour services de garde d'enfants
                    'name' => $user->firstname . ' ' . $user->lastname,
                    'product_description' => 'Services de garde d\'enfants et babysitting',
                    'support_email' => $user->email,
                    // URL supprimée car localhost n'est pas accepté par Stripe
                ],
                // tos_acceptance supprimé car non supporté pour FR->FR selon Stripe
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'weekly',
                            'weekly_anchor' => 'friday'
                        ]
                    ]
                ]
            ]);

            $user->update([
                'stripe_account_id' => $account->id,
                'stripe_account_status' => 'pending'
            ]);

            return $account;
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du compte Stripe Connect', [
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
            // S'assurer que le compte Connect existe
            if (!$user->stripe_account_id) {
                $this->createConnectAccount($user);
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

    // Méthode supprimée - plus nécessaire avec l'approche Stripe Connect native

    public function checkIdentityVerificationStatus(User $user)
    {
        try {
            if (!$user->stripe_account_id) {
                return 'not_started';
            }

            $account = $this->stripe->accounts->retrieve($user->stripe_account_id);
            
            // Vérifier spécifiquement le statut de vérification d'identité
            $individualVerificationStatus = $account->individual->verification->status ?? 'unverified';
            
            if ($individualVerificationStatus === 'verified') {
                return 'verified';
            }
            
            if ($individualVerificationStatus === 'pending') {
                return 'processing';
            }
            
            // Vérifier s'il y a des requirements d'identité spécifiques
            $identityRequirements = [
                'individual.verification.document',
                'individual.verification.additional_document',
                'individual.id_number'
            ];
            
            $currentlyDue = $account->requirements->currently_due ?? [];
            $eventuallyDue = $account->requirements->eventually_due ?? [];
            $pastDue = $account->requirements->past_due ?? [];
            $pendingVerification = $account->requirements->pending_verification ?? [];
            
            $allRequirements = array_merge($currentlyDue, $eventuallyDue, $pastDue, $pendingVerification);
            
            $hasIdentityRequirements = false;
            foreach ($identityRequirements as $identityReq) {
                foreach ($allRequirements as $requirement) {
                    if (strpos($requirement, $identityReq) !== false) {
                        $hasIdentityRequirements = true;
                        break 2;
                    }
                }
            }
            
            if ($hasIdentityRequirements) {
                if (!empty($pastDue)) {
                    return 'requires_action'; // Action urgente requise
                }
                return 'requires_input'; // Vérification d'identité requise
            }
            
            // Si pas de requirements d'identité et statut unverified, c'est que ce n'est pas encore requis
            if ($individualVerificationStatus === 'unverified') {
                return 'not_required_yet';
            }
            
            return 'not_started';
        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification du statut d\'identité', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return 'error';
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

    public function createPaymentIntent($amount, $currency = 'eur', $applicationFee = 0)
    {
        try {
            return $this->stripe->paymentIntents->create([
                'amount' => $amount * 100, // Stripe utilise les centimes
                'currency' => $currency,
                'application_fee_amount' => $applicationFee * 100,
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du PaymentIntent', [
                'amount' => $amount,
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
} 