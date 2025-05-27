# 📥 Commande de Synchronisation pour le Projet Client

## 🎯 Objectif
Cette commande permet à votre autre projet Laravel de récupérer automatiquement les articles et catégories depuis votre SaaS, en ne synchronisant que les nouveaux ou modifiés.

## 🚀 Création de la commande

### 1. **Créer la commande**
```bash
php artisan make:command SyncFromSaas
```

### 2. **Code de la commande**

Remplacez le contenu de `app/Console/Commands/SyncFromSaas.php` :

```php
<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class SyncFromSaas extends Command
{
    protected $signature = 'sync:from-saas 
                           {--saas-url=http://localhost:8000} 
                           {--api-key= : Clé API pour authentification}
                           {--force : Forcer la synchronisation complète}
                           {--dry-run : Simuler sans insérer en base}
                           {--status= : Filtrer par status (draft, published, scheduled)}
                           {--per-page=50 : Nombre d\'articles par page}
                           {--interval=60 : Intervalle en minutes entre les syncs}';
    
    protected $description = 'Synchronise les articles et catégories depuis le SaaS';

    private int $articlesCreated = 0;
    private int $articlesUpdated = 0;
    private int $categoriesCreated = 0;
    private int $categoriesUpdated = 0;

    public function handle()
    {
        $saasUrl = $this->option('saas-url');
        $apiKey = $this->option('api-key');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');
        $status = $this->option('status');
        $perPage = (int) $this->option('per-page');
        $interval = (int) $this->option('interval');

        if (!$apiKey) {
            $this->error("❌ Clé API requise. Utilisez --api-key=votre-cle");
            return 1;
        }

        $this->info("🔄 Synchronisation depuis le SaaS");
        $this->info("🌐 SaaS: {$saasUrl}");
        $this->info("🔑 API Key: " . substr($apiKey, 0, 8) . '...');
        
        if ($dryRun) {
            $this->warn("🧪 Mode DRY-RUN activé - Aucune modification en base");
        }
        
        $this->newLine();

        try {
            // Vérifier si une synchronisation est nécessaire
            $lastSync = $this->getLastSyncTime();
            
            if ($lastSync && !$force) {
                $nextSync = $lastSync->addMinutes($interval);
                if ($nextSync->isFuture()) {
                    $this->info("⏰ Synchronisation pas nécessaire");
                    $this->info("📅 Dernière sync: {$lastSync->format('d/m/Y H:i')}");
                    $this->info("⏳ Prochaine sync: {$nextSync->format('d/m/Y H:i')}");
                    $this->info("💡 Utilisez --force pour forcer la synchronisation");
                    return 0;
                }
            }

            // Préparer les paramètres de requête
            $queryParams = [
                'per_page' => $perPage,
            ];
            
            if ($status) {
                $queryParams['status'] = $status;
            }

            // Ajouter le paramètre "since" si on a une dernière sync
            if ($lastSync && !$force) {
                $queryParams['since'] = $lastSync->toISOString();
                $this->info("📅 Récupération des articles modifiés depuis: {$lastSync->format('d/m/Y H:i')}");
            } else {
                $this->info("🆕 Synchronisation complète - Récupération de tous les articles");
            }

            $this->newLine();
            $this->info("📡 Récupération des articles...");

            // Faire la requête à l'API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-API-Key' => $apiKey,
                ])
                ->get($saasUrl . '/api/articles', $queryParams);

            if (!$response->successful()) {
                $this->error("❌ Erreur API: " . $response->status());
                if ($response->status() === 401) {
                    $this->error("🔑 Vérifiez votre clé API");
                }
                return 1;
            }

            $data = $response->json();
            
            if (!isset($data['success']) || !$data['success']) {
                $this->error("❌ Erreur: " . ($data['message'] ?? 'Erreur inconnue'));
                return 1;
            }

            $articles = $data['articles'] ?? [];
            $siteInfo = $data['site'] ?? null;

            $this->info("📄 Articles récupérés: " . count($articles));
            
            if ($siteInfo) {
                $this->info("🏢 Site: {$siteInfo['name']} ({$siteInfo['domain']})");
            }

            if (empty($articles)) {
                $this->info("✅ Aucun nouvel article à synchroniser");
                if (!$dryRun) {
                    $this->updateLastSyncTime();
                }
                return 0;
            }

            // Traiter les articles
            $this->newLine();
            $this->info("🔄 Traitement des articles...");
            
            $progressBar = $this->output->createProgressBar(count($articles));
            $progressBar->start();

            foreach ($articles as $articleData) {
                if (!$dryRun) {
                    $this->processArticle($articleData);
                } else {
                    // En mode dry-run, juste simuler
                    $existingArticle = Article::where('external_id', $articleData['external_id'])->first();
                    if ($existingArticle) {
                        $this->articlesUpdated++;
                    } else {
                        $this->articlesCreated++;
                    }
                }
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine();

            // Afficher les résultats
            $this->newLine();
            $this->info("📊 Résultats de la synchronisation:");
            $this->table(['Métrique', 'Valeur'], [
                ['Articles récupérés', count($articles)],
                ['Articles créés', $this->articlesCreated],
                ['Articles mis à jour', $this->articlesUpdated],
                ['Catégories créées', $this->categoriesCreated],
                ['Catégories mises à jour', $this->categoriesUpdated],
                ['Mode', $dryRun ? 'DRY-RUN' : 'RÉEL'],
            ]);

            // Mettre à jour le timestamp de dernière sync
            if (!$dryRun) {
                $this->updateLastSyncTime();
            }

            $this->newLine();
            $this->info("✅ Synchronisation terminée avec succès !");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la synchronisation:");
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function processArticle(array $articleData): void
    {
        try {
            DB::transaction(function () use ($articleData) {
                $externalId = $articleData['external_id'];
                
                // Vérifier que l'external_id existe
                if (empty($externalId)) {
                    throw new \Exception("External ID manquant pour l'article: " . ($articleData['title'] ?? 'Sans titre'));
                }
                
                // Chercher l'article existant
                $article = Article::where('external_id', $externalId)->first();

                // Gérer le contenu null
                $content = $articleData['content'];
                if (empty($content)) {
                    $content = $articleData['excerpt'] ?? 'Contenu non disponible';
                }

                $data = [
                    'title' => $articleData['title'] ?? 'Sans titre',
                    'slug' => $articleData['slug'] ?? Str::slug($articleData['title'] ?? 'sans-titre'),
                    'content' => $content,
                    'excerpt' => $articleData['excerpt'] ?? null,
                    'featured_image_url' => $articleData['featured_image_url'] ?? null,
                    'meta_title' => $articleData['meta_title'] ?? null,
                    'meta_description' => $articleData['meta_description'] ?? null,
                    'status' => $articleData['status'] ?? 'draft',
                    'author_name' => $articleData['author_name'] ?? null,
                    'author_bio' => $articleData['author_bio'] ?? null,
                    'external_id' => $externalId,
                    'reading_time' => $articleData['reading_time'] ?? 1,
                    'is_featured' => $articleData['is_featured'] ?? false,
                    'synced_at' => now(),
                ];

                // Gérer la date de publication
                if (isset($articleData['published_at']) && $articleData['published_at']) {
                    $data['published_at'] = Carbon::parse($articleData['published_at']);
                }

                if ($article) {
                    // Mise à jour
                    $article->update($data);
                    $this->articlesUpdated++;
                } else {
                    // Création
                    $article = Article::create($data);
                    $this->articlesCreated++;
                }

                // Gérer les catégories
                if (isset($articleData['categories']) && is_array($articleData['categories'])) {
                    $this->syncCategories($article, $articleData['categories']);
                }
            });

        } catch (\Exception $e) {
            $externalId = $articleData['external_id'] ?? 'ID manquant';
            $this->warn("⚠️  Erreur lors du traitement de l'article {$externalId}: " . $e->getMessage());
        }
    }

    private function syncCategories($article, array $categoryNames): void
    {
        $categoryIds = [];

        foreach ($categoryNames as $categoryName) {
            if (empty($categoryName)) continue;

            // Chercher ou créer la catégorie
            $category = Category::where('name', $categoryName)->first();
            
            if (!$category) {
                $category = Category::create([
                    'name' => $categoryName,
                    'slug' => Str::slug($categoryName),
                ]);
                $this->categoriesCreated++;
            } else {
                // Optionnel: mettre à jour le slug si nécessaire
                $newSlug = Str::slug($categoryName);
                if ($category->slug !== $newSlug) {
                    $category->update(['slug' => $newSlug]);
                    $this->categoriesUpdated++;
                }
            }

            $categoryIds[] = $category->id;
        }

        // Synchroniser les relations
        $article->categories()->sync($categoryIds);
    }

    private function getLastSyncTime(): ?Carbon
    {
        // Option 1: Utiliser une table de configuration
        $lastSync = DB::table('settings')
            ->where('key', 'last_saas_sync')
            ->value('value');

        if ($lastSync) {
            return Carbon::parse($lastSync);
        }

        // Option 2: Utiliser le dernier article synchronisé
        $lastArticle = Article::whereNotNull('synced_at')
            ->orderBy('synced_at', 'desc')
            ->first();

        return $lastArticle ? $lastArticle->synced_at : null;
    }

    private function updateLastSyncTime(): void
    {
        // Option 1: Utiliser une table de configuration
        DB::table('settings')->updateOrInsert(
            ['key' => 'last_saas_sync'],
            ['value' => now()->toISOString(), 'updated_at' => now()]
        );
    }
}
```

## 🗄️ Migrations nécessaires

### 1. **Migration pour la table articles**
```bash
php artisan make:migration create_articles_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content');
            $table->text('excerpt')->nullable();
            $table->string('featured_image_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->enum('status', ['draft', 'published', 'scheduled'])->default('draft');
            $table->string('author_name')->nullable();
            $table->text('author_bio')->nullable();
            $table->string('external_id')->unique()->nullable(); // ID depuis le SaaS
            $table->integer('reading_time')->default(1);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('synced_at')->nullable(); // Dernière synchronisation
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['status', 'published_at']);
            $table->index('external_id');
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

### 2. **Migration pour la table categories**
```bash
php artisan make:migration create_categories_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
```

### 3. **Migration pour la table pivot**
```bash
php artisan make:migration create_article_category_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['article_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_category');
    }
};
```

### 4. **Migration pour la table settings**
```bash
php artisan make:migration create_settings_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
```

## 📝 Modèles Eloquent

### 1. **Modèle Article**
```bash
php artisan make:model Article
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image_url',
        'meta_title',
        'meta_description',
        'status',
        'author_name',
        'author_bio',
        'external_id',
        'reading_time',
        'is_featured',
        'published_at',
        'synced_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'synced_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    // Scope pour les articles publiés
    public function scopePublished($query)
    {
        return $query->where('status', 'published')
                    ->where('published_at', '<=', now());
    }

    // Scope pour les articles synchronisés
    public function scopeSynced($query)
    {
        return $query->whereNotNull('synced_at');
    }
}
```

### 2. **Modèle Category**
```bash
php artisan make:model Category
```

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }
}
```

## 🚀 Utilisation

### **Commandes disponibles**

```bash
# Première synchronisation complète
php artisan sync:from-saas --api-key=votre-cle-api

# Test en mode dry-run
php artisan sync:from-saas --api-key=votre-cle-api --dry-run

# Synchronisation incrémentale (seulement les nouveaux/modifiés)
php artisan sync:from-saas --api-key=votre-cle-api

# Forcer une synchronisation complète
php artisan sync:from-saas --api-key=votre-cle-api --force

# Synchroniser seulement les articles publiés
php artisan sync:from-saas --api-key=votre-cle-api --status=published

# Avec URL personnalisée
php artisan sync:from-saas --api-key=votre-cle-api --saas-url=https://votre-saas.com
```

### **Automatisation avec Cron**

Ajoutez dans `app/Console/Kernel.php` :

```php
protected function schedule(Schedule $schedule)
{
    // Synchronisation toutes les heures
    $schedule->command('sync:from-saas --api-key=' . env('SAAS_API_KEY'))
             ->hourly()
             ->withoutOverlapping()
             ->runInBackground();
}
```

## 🔧 Configuration

### **Variables d'environnement**

Ajoutez dans votre `.env` :

```env
SAAS_URL=http://localhost:8000
SAAS_API_KEY=votre-cle-api-ici
```

## 📊 Fonctionnalités

✅ **Synchronisation intelligente** : Seulement les nouveaux/modifiés  
✅ **Gestion des catégories** : Création automatique des catégories  
✅ **Mode dry-run** : Test sans modification  
✅ **Gestion d'erreurs** : Transactions et logs détaillés  
✅ **Progress bar** : Suivi visuel des synchronisations  
✅ **Filtres** : Par statut, pagination, etc.  
✅ **Automatisation** : Compatible avec les tâches cron  

---

**🎉 Votre projet client est maintenant prêt à synchroniser automatiquement avec votre SaaS !** 