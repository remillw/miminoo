# 📧 Configuration Waitlist - Collecte automatique d'emails

## 🎯 **Solutions disponibles**

### ✅ **Solution 1 : Google Sheets (Recommandée - GRATUITE)**
### 🔄 **Solution 2 : Base de données Laravel (Plus professionnelle)**  
### 🚀 **Solution 3 : Services tiers (Typeform, Airtable, etc.)**

---

## 📊 **Solution 1 : Google Sheets - Configuration complète**

### **Étape 1 : Créer le Google Sheet**
1. Allez sur [Google Sheets](https://sheets.google.com)
2. Créez un nouveau tableur
3. Nommez-le "Waitlist Trouve ta Babysitter"
4. Dans la première ligne, ajoutez ces en-têtes :
   - **A1** : Email
   - **B1** : Role
   - **C1** : Date
   - **D1** : Source

### **Étape 2 : Configurer Google Apps Script**
1. Dans votre Google Sheet, allez dans **Extensions** > **Apps Script**
2. Supprimez le code par défaut
3. Copiez-collez le contenu du fichier `google-apps-script-waitlist.gs`
4. Remplacez `VOTRE_SHEET_ID_ICI` par l'ID de votre sheet (trouvé dans l'URL)
5. Remplacez `votre-email@gmail.com` par votre vraie adresse email

### **Étape 3 : Déployer le Web App**
1. Cliquez sur **Déployer** > **Nouvelle déploiement**
2. Cliquez sur l'icône ⚙️ à côté de "Type"
3. Sélectionnez **Application web**
4. Configuration :
   - **Description** : "Waitlist API"
   - **Exécuter en tant que** : Moi
   - **Accès autorisé à** : Tout le monde
5. Cliquez **Déployer**
6. **Copiez l'URL du Web App** fournie

### **Étape 4 : Mettre à jour votre code Vue**
Dans `resources/js/pages/waitlist.vue`, remplacez :
```typescript
const GOOGLE_SCRIPT_URL = 'https://script.google.com/macros/s/VOTRE_SCRIPT_ID/exec';
```
Par votre vraie URL du Web App.

### **✅ Avantages de Google Sheets :**
- ✅ **100% GRATUIT**
- ✅ **Facile à configurer** (15 minutes)
- ✅ **Interface familière** 
- ✅ **Export Excel/CSV** intégré
- ✅ **Notifications email** automatiques
- ✅ **Partage d'équipe** simple
- ✅ **Sauvegarde automatique**

---

## 🔄 **Solution 2 : Base de données Laravel (Optionnelle)**

Si vous préférez une solution plus professionnelle intégrée à votre application :

### **Créer la migration**
```bash
php artisan make:migration create_waitlist_table
```

### **Code de la migration**
```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('waitlist', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->enum('role', ['parent', 'babysitter']);
            $table->string('source')->default('waitlist');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waitlist');
    }
};
```

### **Créer le modèle**
```bash
php artisan make:model WaitlistEntry
```

### **Créer le contrôleur**
```bash
php artisan make:controller WaitlistController
```

### **Route API**
```php
// routes/api.php
Route::post('/waitlist', [WaitlistController::class, 'store']);
```

---

## 🚀 **Solution 3 : Services tiers (Alternatives)**

### **Typeform (Recommandé)**
- ✅ Interface magnifique
- ✅ Analytics intégrées  
- ✅ Intégrations multiples
- 💰 Gratuit jusqu'à 100 réponses/mois

### **Airtable**
- ✅ Base de données moderne
- ✅ API puissante
- ✅ Interface intuitive
- 💰 Gratuit jusqu'à 1,200 enregistrements

### **Zapier + Google Sheets**
- ✅ Automatisation avancée
- ✅ Intégrations multiples
- ✅ Webhooks
- 💰 Freemium

---

## 🎯 **Recommandation finale**

**Pour commencer rapidement : Solution 1 (Google Sheets)**
- Configuration en 15 minutes
- 100% gratuit
- Parfait pour les premiers 1000 emails

**Pour évoluer : Solution 2 (Laravel)**
- Plus de contrôle
- Intégration native
- Évolutif

**Voulez-vous que je vous aide à configurer une de ces solutions ?** 🤔 