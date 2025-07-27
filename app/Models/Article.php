<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image_url',
        'cover_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'og_title',
        'og_description',
        'og_image',
        'twitter_title',
        'twitter_description',
        'twitter_image',
        'schema_markup',
        'status',
        'published_at',
        'scheduled_at',
        'author_name',
        'author_bio',
        'external_id',
        'source',
        'webhook_received_at',
        'webhook_data',
        'is_featured',
        'is_synced',
        'reading_time',
        'word_count',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'webhook_received_at' => 'datetime',
        'webhook_data' => 'array',
        'meta_keywords' => 'array',
        'schema_markup' => 'array',
        'is_featured' => 'boolean',
        'is_synced' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
                
                // Assurer l'unicité du slug
                $originalSlug = $article->slug;
                $counter = 1;
                while (static::where('slug', $article->slug)->exists()) {
                    $article->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            if (empty($article->meta_title)) {
                $article->meta_title = $article->title;
            }

            if (empty($article->meta_description) && $article->excerpt) {
                $article->meta_description = Str::limit($article->excerpt, 160);
            }

            // Calculer le temps de lecture
            if ($article->content) {
                $wordCount = str_word_count(strip_tags($article->content));
                $article->reading_time = ceil($wordCount / 200); // 200 mots/minute
            }
        });
    }

    // Relations
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Accessors
    public function getExcerptAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Générer un extrait depuis le contenu si pas d'extrait défini
        return Str::limit(strip_tags($this->content), 160);
    }

    public function getReadingTimeAttribute($value)
    {
        if ($value) {
            return $value;
        }

        // Calculer le temps de lecture si pas défini
        $wordCount = str_word_count(strip_tags($this->content));
        return ceil($wordCount / 200);
    }

    public function getFeaturedImageUrlAttribute()
    {
        // Si featured_image_url est défini, l'utiliser
        if ($this->attributes['featured_image_url'] ?? null) {
            return $this->attributes['featured_image_url'];
        }

        // Sinon, utiliser cover_image avec l'URL complète
        if ($this->cover_image) {
            return asset('storage/' . $this->cover_image);
        }

        // Fallback sur une image par défaut
        return asset('storage/article-default.jpg');
    }

    // Méthodes utilitaires
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at && 
               $this->published_at->isPast();
    }
} 