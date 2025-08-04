<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Stripe\Stripe;
use Stripe\File;
use Stripe\Identity\VerificationSession;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class StripeFileUploadController extends Controller
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * 🚀 Génère les paramètres pour upload direct côté frontend
     * 
     * Le frontend utilisera directement l'API Stripe avec ces paramètres.
     * AUCUN fichier ne transite par ce serveur !
     */
    public function getUploadConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'purpose' => 'required|string|in:identity_document,additional_verification',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();
        $purpose = $request->input('purpose', 'identity_document');

        // Vérifier que l'utilisateur a un compte Stripe
        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'error' => 'No Stripe account found. Please complete onboarding first.'
            ], 400);
        }

        Log::info("🚀 Upload config requested for direct frontend upload", [
            'user_id' => $user->id,
            'purpose' => $purpose,
            'stripe_account_id' => $user->stripe_account_id
        ]);

        return response()->json([
            'success' => true,
            'config' => [
                'stripe_publishable_key' => config('services.stripe.key'),
                'stripe_account_id' => $user->stripe_account_id,
                'purpose' => $purpose,
                'upload_url' => 'https://files.stripe.com/v1/files',
                'validation' => [
                    'max_files' => 5,
                    'max_size_mb' => 10,
                    'allowed_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf']
                ]
            ],
            'message' => 'Configuration pour upload direct côté frontend'
        ]);
    }

    /**
     * 🔗 Lier un fichier uploadé au compte Connect pour résoudre les requirements
     */
    public function linkFileToAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'stripe_file_id' => 'required|string',
            'filename' => 'required|string', 
            'purpose' => 'required|string|in:identity_document,additional_verification'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'error' => 'No Stripe Connect account found. Please complete onboarding first.'
            ], 400);
        }

        try {
            $stripeFileId = $request->input('stripe_file_id');
            $filename = $request->input('filename');
            
            // Vérifier que le fichier existe sur Stripe
            $file = \Stripe\File::retrieve($stripeFileId);
            
            if ($file->purpose !== 'identity_document') {
                Log::warning('File purpose mismatch for single file link', [
                    'file_id' => $stripeFileId,
                    'expected' => 'identity_document',
                    'actual' => $file->purpose
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => 'File purpose mismatch'
                ], 422);
            }

            // Créer un account token avec le document et mettre à jour le compte
            $accountToken = \Stripe\Token::create([
                'account' => [
                    'individual' => [
                        'verification' => [
                            'document' => [
                                'front' => $stripeFileId
                            ]
                        ]
                    ]
                ]
            ]);

            $account = \Stripe\Account::update($user->stripe_account_id, [
                'account_token' => $accountToken->id
            ]);

            Log::info('✅ Single file linked to Connect account for identity verification', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'file_id' => $stripeFileId,
                'filename' => $filename
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File successfully linked to your Stripe Connect account for identity verification',
                'file_id' => $stripeFileId,
                'filename' => $filename,
                'account_id' => $user->stripe_account_id
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('❌ Failed to link single file to Connect account', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'file_id' => $request->input('stripe_file_id'),
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to link file to your account',
                'details' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ General error linking single file to Connect account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error while linking file'
            ], 500);
        }
    }

    /**
     * 🔗 Lier les fichiers uploadés au compte Connect pour résoudre les requirements
     * 
     * Cette méthode est appelée après que les fichiers ont été uploadés
     * directement vers l'API Stripe Files par le frontend.
     */
    public function linkFilesToAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:5',
            'files.*.stripe_file_id' => 'required|string',
            'files.*.filename' => 'required|string',
            'files.*.purpose' => 'required|string|in:identity_document,additional_verification'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'error' => 'No Stripe Connect account found. Please complete onboarding first.'
            ], 400);
        }

        try {
            $uploadedFiles = $request->input('files');
            $results = [];

            foreach ($uploadedFiles as $fileData) {
                $stripeFileId = $fileData['stripe_file_id'];
                
                // Vérifier que le fichier existe sur Stripe
                $file = \Stripe\File::retrieve($stripeFileId);
                
                if ($file->purpose !== 'identity_document') {
                    Log::warning('File purpose mismatch', [
                        'file_id' => $stripeFileId,
                        'expected' => 'identity_document',
                        'actual' => $file->purpose
                    ]);
                    continue;
                }

                // Attacher le fichier au compte Connect pour résoudre le requirement
                $accountUpdateData = [
                    'individual' => [
                        'verification' => [
                            'document' => [
                                'front' => $stripeFileId
                            ]
                        ]
                    ]
                ];

                $account = \Stripe\Account::update(
                    $user->stripe_account_id,
                    $accountUpdateData
                );

                $results[] = [
                    'file_id' => $stripeFileId,
                    'filename' => $fileData['filename'],
                    'linked_to_account' => $user->stripe_account_id,
                    'status' => 'linked'
                ];

                Log::info('✅ File linked to Connect account for identity verification', [
                    'user_id' => $user->id,
                    'stripe_account_id' => $user->stripe_account_id,
                    'file_id' => $stripeFileId,
                    'filename' => $fileData['filename']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Files successfully linked to your Stripe Connect account for identity verification',
                'linked_files' => $results,
                'account_id' => $user->stripe_account_id,
                'next_step' => 'Stripe will now process your identity documents. This may take a few minutes to a few hours.'
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('❌ Failed to link files to Connect account', [
                'user_id' => $user->id,
                'stripe_account_id' => $user->stripe_account_id,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to link files to your account',
                'details' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ], 422);

        } catch (\Exception $e) {
            Log::error('❌ General error linking files to Connect account', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error while linking files'
            ], 500);
        }
    }
    
    /**
     * 🚀 Upload de fichiers vers Stripe et liaison automatique au compte Connect
     * 
     * Cette méthode fait l'upload côté serveur avec la clé secrète et lie 
     * automatiquement les fichiers au compte Connect pour résoudre les requirements.
     */
    public function uploadFiles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:5',
            'files.*' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240', // 10MB max
            'purpose' => 'string|in:identity_document,additional_verification'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        if (!$user->stripe_account_id) {
            return response()->json([
                'success' => false,
                'error' => 'No Stripe Connect account found. Please complete onboarding first.'
            ], 400);
        }

        try {
            $files = $request->file('files');
            $purpose = $request->input('purpose', 'identity_document');
            $uploadedFiles = [];
            $errors = [];

            foreach ($files as $index => $file) {
                try {
                    // Validation du contenu
                    $this->validateFileContent($file);

                    // 1. Upload vers Stripe avec la clé secrète
                    $stripeFile = \Stripe\File::create([
                        'purpose' => $purpose,
                        'file' => fopen($file->getRealPath(), 'r'),
                    ]);

                    Log::info('✅ File uploaded to Stripe with secret key', [
                        'user_id' => $user->id,
                        'file_id' => $stripeFile->id,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $stripeFile->size,
                        'purpose' => $stripeFile->purpose
                    ]);

                    // 2. Créer un account token avec le document et mettre à jour le compte
                    $accountToken = \Stripe\Token::create([
                        'account' => [
                            'individual' => [
                                'verification' => [
                                    'document' => [
                                        'front' => $stripeFile->id
                                    ]
                                ]
                            ]
                        ]
                    ]);

                    \Stripe\Account::update($user->stripe_account_id, [
                        'account_token' => $accountToken->id
                    ]);

                    Log::info('✅ File linked to Connect account automatically', [
                        'user_id' => $user->id,
                        'stripe_account_id' => $user->stripe_account_id,
                        'file_id' => $stripeFile->id
                    ]);

                    $uploadedFiles[] = [
                        'stripe_file_id' => $stripeFile->id,
                        'filename' => $file->getClientOriginalName(),
                        'size' => $stripeFile->size,
                        'purpose' => $stripeFile->purpose,
                        'linked_to_account' => true
                    ];

                } catch (\Stripe\Exception\ApiErrorException $e) {
                    Log::error('❌ Stripe API error during upload/link', [
                        'user_id' => $user->id,
                        'file_index' => $index,
                        'error' => $e->getMessage(),
                        'stripe_code' => $e->getStripeCode()
                    ]);

                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage(),
                        'stripe_code' => $e->getStripeCode()
                    ];

                } catch (\Exception $e) {
                    Log::error('❌ General error during upload/link', [
                        'user_id' => $user->id,
                        'file_index' => $index,
                        'error' => $e->getMessage()
                    ]);

                    $errors[] = [
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ];
                }
            }

            return response()->json([
                'success' => count($uploadedFiles) > 0,
                'message' => count($uploadedFiles) > 0 
                    ? 'Files successfully uploaded and linked to your Stripe Connect account'
                    : 'All uploads failed',
                'uploaded_files' => $uploadedFiles,
                'errors' => $errors,
                'account_id' => $user->stripe_account_id,
                'total_uploaded' => count($uploadedFiles),
                'total_errors' => count($errors)
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Critical error in upload process', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Critical error during upload process',
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validation supplémentaire du contenu des fichiers
     */
    private function validateFileContent($file): void
    {
        // Vérifier la signature du fichier pour éviter les fichiers malveillants
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMimeType = finfo_file($finfo, $file->getRealPath());
        finfo_close($finfo);
        
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png', 
            'application/pdf'
        ];
        
        if (!in_array($detectedMimeType, $allowedMimeTypes)) {
            throw new \Exception('File type not allowed: ' . $detectedMimeType);
        }
        
        // Vérifier que le fichier n'est pas vide
        if ($file->getSize() < 1024) { // Min 1KB
            throw new \Exception('File too small, minimum 1KB required');
        }
    }

    /**
     * Créer une session de vérification d'identité
     */
    public function createVerificationSession(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:document,id_number',
            'return_url' => 'required|url',
            'options' => 'array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            $user = auth()->user();
            
            $sessionData = [
                'type' => $request->input('type', 'document'),
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'created_via' => 'api'
                ],
                'return_url' => $request->input('return_url'),
            ];

            // Configuration par défaut pour les documents
            if ($request->input('type') === 'document') {
                $sessionData['options'] = [
                    'document' => [
                        'allowed_types' => ['driving_license', 'passport', 'id_card'],
                        'require_id_number' => false,
                        'require_live_capture' => false,
                        'require_matching_selfie' => false,
                    ],
                ];
            }

            // Permettre la personnalisation des options
            if ($request->has('options')) {
                $sessionData['options'] = array_merge_recursive(
                    $sessionData['options'] ?? [],
                    $request->input('options')
                );
            }

            $session = VerificationSession::create($sessionData);

            // Sauvegarder l'ID de session dans la base pour le suivi
            $user->update([
                'stripe_identity_session_id' => $session->id,
                'identity_verification_status' => 'pending'
            ]);

            Log::info("Identity verification session created", [
                'session_id' => $session->id,
                'user_id' => $user->id,
                'type' => $session->type
            ]);

            return response()->json([
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'type' => $session->type,
                    'url' => $session->url,
                    'return_url' => $session->return_url,
                    'created' => $session->created,
                ],
                'message' => 'Verification session created successfully'
            ]);

        } catch (ApiErrorException $e) {
            Log::error("Failed to create identity verification session", [
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create verification session',
                'details' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode()
            ], 422);

        } catch (\Exception $e) {
            Log::error("General error creating verification session", [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Vérifier le statut d'une session de vérification
     */
    public function checkVerificationStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user->stripe_identity_session_id) {
            return response()->json([
                'success' => false,
                'error' => 'No verification session found for this user'
            ], 404);
        }

        try {
            $session = VerificationSession::retrieve($user->stripe_identity_session_id);

            // Mettre à jour le statut dans la base si nécessaire
            if ($session->status !== $user->identity_verification_status) {
                $updateData = ['identity_verification_status' => $session->status];
                
                if ($session->status === 'verified') {
                    $updateData['identity_verified_at'] = now();
                }
                
                $user->update($updateData);
            }

            return response()->json([
                'success' => true,
                'session' => [
                    'id' => $session->id,
                    'status' => $session->status,
                    'type' => $session->type,
                    'created' => $session->created,
                    'last_verification_report' => $session->last_verification_report,
                ],
                'user_status' => $user->fresh()->identity_verification_status
            ]);

        } catch (ApiErrorException $e) {
            Log::error("Failed to retrieve verification session", [
                'session_id' => $user->stripe_identity_session_id,
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve verification status',
                'details' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Webhook pour traiter les événements de vérification d'identité
     */
    public function handleVerificationWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig_header = $request->server('HTTP_STRIPE_SIGNATURE');
        $endpoint_secret = config('services.stripe.webhook.secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);

            Log::info("Identity verification webhook received", [
                'event_type' => $event['type'],
                'event_id' => $event['id']
            ]);

            switch ($event['type']) {
                case 'identity.verification_session.verified':
                    $this->handleVerificationSuccess($event['data']['object']);
                    break;
                
                case 'identity.verification_session.requires_input':
                    $this->handleVerificationRequiresInput($event['data']['object']);
                    break;
                
                case 'identity.verification_session.canceled':
                    $this->handleVerificationCanceled($event['data']['object']);
                    break;

                default:
                    Log::info("Unhandled identity verification event type", [
                        'event_type' => $event['type']
                    ]);
            }

            return response()->json(['success' => true]);

        } catch (\UnexpectedValueException $e) {
            Log::error("Invalid payload in identity verification webhook", [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error("Invalid signature in identity verification webhook", [
                'error' => $e->getMessage()
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }
    }

    private function handleVerificationSuccess($session): void
    {
        $user = \App\Models\User::where('stripe_identity_session_id', $session['id'])->first();
        
        if ($user) {
            $user->update([
                'identity_verification_status' => 'verified',
                'identity_verified_at' => now(),
                'identity_verification_error' => null
            ]);

            Log::info("User identity verification completed", [
                'user_id' => $user->id,
                'session_id' => $session['id']
            ]);

            // Vous pouvez ajouter ici d'autres actions (notifications, etc.)
        }
    }

    private function handleVerificationRequiresInput($session): void
    {
        $user = \App\Models\User::where('stripe_identity_session_id', $session['id'])->first();
        
        if ($user) {
            $user->update([
                'identity_verification_status' => 'requires_input'
            ]);
        }
    }

    private function handleVerificationCanceled($session): void
    {
        $user = \App\Models\User::where('stripe_identity_session_id', $session['id'])->first();
        
        if ($user) {
            $user->update([
                'identity_verification_status' => 'canceled',
                'identity_verification_error' => 'User canceled verification'
            ]);
        }
    }
}