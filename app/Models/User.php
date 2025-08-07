<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Role;
use App\Models\ParentProfile;
use App\Models\BabysitterProfile;
use App\Models\Address;
use App\Models\Ad;
use App\Models\AdApplication;
use App\Models\Review;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'email',
        'password',
        'phone',
        'date_of_birth',
        'is_verified',
        'status',
        'google_id',
        'apple_id',
        'provider',
        'is_social_account',
        'social_data_locked',
        'avatar',
        'profile_photos',
        'address_id',
        'email_verified_at',
        'stripe_account_id',
        'stripe_identity_session_id',
        'stripe_account_status',
        'identity_verified_at',
        'email_notifications',
        'push_notifications',
        'sms_notifications',
        'language',
        'device_token',
        'device_type',
        'notification_provider',
        'device_token_updated_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

   

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'profile_photos' => 'array',
            'is_verified' => 'boolean',
            'is_social_account' => 'boolean',
            'social_data_locked' => 'boolean',
            'email_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'identity_verified_at' => 'datetime',
            'device_token_updated_at' => 'datetime',
        ];
    }

    /**
     * Vérifier si l'utilisateur a un mot de passe défini
     */
    public function hasPassword(): bool
    {
        return !empty($this->password);
    }

    /**
     * Vérifier si l'utilisateur utilise uniquement Google
     */
    public function isGoogleOnlyUser(): bool
    {
        return !empty($this->google_id) && empty($this->password);
    }

    /**
     * Vérifier si l'utilisateur utilise uniquement Apple
     */
    public function isAppleOnlyUser(): bool
    {
        return !empty($this->apple_id) && empty($this->password);
    }

    /**
     * Vérifier si l'utilisateur utilise uniquement un compte social
     */
    public function isSocialOnlyUser(): bool
    {
        return $this->is_social_account && !$this->hasPassword();
    }

    /**
     * Vérifier si les données de l'utilisateur sont verrouillées (viennent d'un provider social)
     */
    public function hasSocialDataLocked(): bool
    {
        return $this->social_data_locked;
    }

    /**
     * Obtenir le provider social principal
     */
    public function getPrimaryProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * Vérifier si l'utilisateur a un provider social spécifique
     */
    public function hasProvider(string $provider): bool
    {
        return match($provider) {
            'google' => !empty($this->google_id),
            'apple' => !empty($this->apple_id),
            default => false,
        };
    }

    /**
     * Obtenir l'URL de l'avatar (photo de profil principale)
     */
    public function getAvatarUrl()
    {
        if ($this->avatar) {
            // Si c'est une URL complète (provider social), la retourner directement
            if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
                return $this->avatar;
            }
            // Si ça commence déjà par /storage, le retourner tel quel
            if (str_starts_with($this->avatar, '/storage/')) {
                return asset($this->avatar);
            }
            // Sinon, construire l'URL depuis le storage
            return asset('storage/' . $this->avatar);
        }
        
        return asset('storage/babysitter-test.png'); // Image par défaut
    }

    /**
     * Obtenir les URLs des photos supplémentaires (pour babysitters)
     */
    public function getAdditionalPhotosUrls()
    {
        // Accéder aux photos depuis le profil babysitter
        if (!$this->babysitterProfile || !$this->babysitterProfile->profile_photos || !is_array($this->babysitterProfile->profile_photos)) {
            return [];
        }
        
        return array_map(function($photo) {
            if (str_starts_with($photo, 'data:image')) {
                // Si c'est une image base64, la retourner telle quelle
                return $photo;
            }
            // Sinon, construire l'URL depuis le storage
            return asset('storage/' . $photo);
        }, $this->babysitterProfile->profile_photos);
    }

    /**
     * Relation many-to-many avec les rôles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * Vérifier si l'utilisateur a un rôle spécifique
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Vérifier si l'utilisateur a un des rôles spécifiés
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Assigner un rôle à l'utilisateur
     */
    public function assignRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role && !$this->hasRole($roleName)) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * Retirer un rôle à l'utilisateur
     */
    public function removeRole(string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function parentProfile()
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function babysitterProfile()
    {
        return $this->hasOne(BabysitterProfile::class);
    }

    /**
     * Relation avec les annonces (pour les parents)
     */
    public function ads()
    {
        return $this->hasMany(Ad::class, 'parent_id');
    }

    /**
     * Relation avec les candidatures (pour les babysitters)
     */
    public function applications()
    {
        return $this->hasMany(AdApplication::class, 'babysitter_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'reviewed_id');
    }

    public function givenReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function averageRating(): float
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function totalReviews(): int
    {
        return $this->reviews()->count();
    }
}
