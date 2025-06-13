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
                    'url' => config('app.url'), // URL de votre site
                ],
                'tos_acceptance' => [
                    'service_agreement' => 'recipient',
                ],
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
} 