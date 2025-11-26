# 🛠️ Commandes Artisan - Training Platform API

## 📋 Installation & Configuration

### Installation initiale

```bash
# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Générer la clé d'application
php artisan key:generate

# Créer le lien symbolique pour le stockage
php artisan storage:link
```

---

## 🗄️ Base de Données

### Migrations

```bash
# Exécuter toutes les migrations
php artisan migrate

# Exécuter avec confirmation automatique
php artisan migrate --force

# Réinitialiser et ré-exécuter toutes les migrations
php artisan migrate:fresh

# Réinitialiser et exécuter les seeders
php artisan migrate:fresh --seed

# Rollback de la dernière migration
php artisan migrate:rollback

# Rollback de toutes les migrations
php artisan migrate:reset

# Vérifier le statut des migrations
php artisan migrate:status
```

### Seeders

```bash
# Exécuter tous les seeders
php artisan db:seed

# Exécuter un seeder spécifique
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
php artisan db:seed --class=AdminSeeder
php artisan db:seed --class=CategorySeeder

# Forcer l'exécution en production
php artisan db:seed --force
```

### Base de données complète

```bash
# Réinitialiser complètement la base de données
php artisan migrate:fresh --seed
```

---

## 👤 Gestion des Utilisateurs

### Créer des comptes de test

```bash
# Via Tinker
php artisan tinker

# Créer un admin
$admin = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$admin->assignRole('admin');

# Créer un instructeur
$instructor = User::create([
    'name' => 'John Instructor',
    'email' => 'instructor@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$instructor->assignRole('instructor');

# Créer un étudiant
$student = User::create([
    'name' => 'Jane Student',
    'email' => 'student@example.com',
    'password' => Hash::make('password'),
    'email_verified_at' => now(),
]);
$student->assignRole('student');
```

---

## 🧹 Cache & Optimisation

### Nettoyer les caches

```bash
# Nettoyer tous les caches
php artisan optimize:clear

# Nettoyer le cache de l'application
php artisan cache:clear

# Nettoyer le cache de configuration
php artisan config:clear

# Nettoyer le cache des routes
php artisan route:clear

# Nettoyer le cache des vues
php artisan view:clear

# Nettoyer le cache des événements
php artisan event:clear
```

### Optimiser pour la production

```bash
# Mettre en cache la configuration
php artisan config:cache

# Mettre en cache les routes
php artisan route:cache

# Mettre en cache les vues
php artisan view:cache

# Mettre en cache les événements
php artisan event:cache

# Optimisation complète
php artisan optimize
```

---

## 🔧 Développement

### Serveur de développement

```bash
# Démarrer le serveur sur le port par défaut (8000)
php artisan serve

# Démarrer sur un port spécifique
php artisan serve --port=8080

# Démarrer sur un hôte spécifique
php artisan serve --host=0.0.0.0 --port=8000
```

### Tinker (REPL)

```bash
# Ouvrir Tinker
php artisan tinker

# Exemples d'utilisation
User::count()
Course::where('status', 'published')->get()
Payment::latest()->first()
```

### Tests

```bash
# Exécuter tous les tests
php artisan test

# Exécuter un test spécifique
php artisan test --filter CourseTest

# Tests avec couverture
php artisan test --coverage

# Tests en parallèle
php artisan test --parallel
```

---

## 📦 Queues (Files d'attente)

### Travailler avec les queues

```bash
# Démarrer un worker
php artisan queue:work

# Worker avec Redis
php artisan queue:work redis

# Worker avec tentatives
php artisan queue:work --tries=3

# Worker avec timeout
php artisan queue:work --timeout=60

# Redémarrer tous les workers
php artisan queue:restart

# Écouter les jobs échoués
php artisan queue:failed

# Réessayer un job échoué
php artisan queue:retry 1

# Réessayer tous les jobs échoués
php artisan queue:retry all

# Supprimer les jobs échoués
php artisan queue:flush
```

---

## 📧 Emails & Notifications

### Tester les emails

```bash
# Via Tinker
php artisan tinker

# Envoyer un email de test
use App\Models\User;
use App\Notifications\WelcomeNotification;

$user = User::first();
$user->notify(new WelcomeNotification());
```

---

## 🔒 Sanctum (Authentification API)

### Gérer les tokens

```bash
# Via Tinker
php artisan tinker

# Créer un token pour un utilisateur
$user = User::find(1);
$token = $user->createToken('auth_token')->plainTextToken;
echo $token;

# Révoquer tous les tokens d'un utilisateur
$user = User::find(1);
$user->tokens()->delete();

# Lister les tokens actifs
use Laravel\Sanctum\PersonalAccessToken;
PersonalAccessToken::all();
```

---

## 📊 Commandes Personnalisées Utiles

### Statistiques de la plateforme

Créez ces commandes pour faciliter la gestion :

```bash
# app/Console/Commands/PlatformStats.php
php artisan make:command PlatformStats

# Utilisation
php artisan platform:stats
```

**Exemple de commande** :

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\{User, Course, Payment, Enrollment};

class PlatformStats extends Command
{
    protected $signature = 'platform:stats';
    protected $description = 'Afficher les statistiques de la plateforme';

    public function handle()
    {
        $this->info('📊 Statistiques de la plateforme');
        $this->newLine();
        
        $this->table(
            ['Métrique', 'Valeur'],
            [
                ['Utilisateurs totaux', User::count()],
                ['Instructeurs', User::whereHas('roles', fn($q) => $q->where('slug', 'instructor'))->count()],
                ['Étudiants', User::whereHas('roles', fn($q) => $q->where('slug', 'student'))->count()],
                ['Formations totales', Course::count()],
                ['Formations publiées', Course::where('status', 'published')->count()],
                ['Inscriptions actives', Enrollment::where('status', 'active')->count()],
                ['Revenus totaux', number_format(Payment::where('status', 'completed')->sum('amount'), 2) . ' USD'],
            ]
        );
    }
}
```

### Nettoyer les anciennes données

```bash
# Créer la commande
php artisan make:command CleanOldData

# Exemples d'utilisation
php artisan platform:clean-old-submissions --days=90
php artisan platform:clean-expired-enrollments
php artisan platform:clean-temp-files
```

### Générer des rapports

```bash
# Rapport mensuel
php artisan report:monthly

# Rapport instructeur
php artisan report:instructor --id=1

# Rapport revenus
php artisan report:revenue --start=2024-01-01 --end=2024-12-31
```

---

## 🐛 Debugging

### Mode debug

```bash
# Activer le mode debug dans .env
APP_DEBUG=true

# Afficher les logs en temps réel
tail -f storage/logs/laravel.log

# Avec filtrage
tail -f storage/logs/laravel.log | grep ERROR
```

### Telescope (développement)

```bash
# Installer Telescope
composer require laravel/telescope --dev

# Publier les assets
php artisan telescope:install

# Migrer
php artisan migrate

# Accéder à Telescope
# http://localhost:8000/telescope
```

### Query debugging

```bash
# Via Tinker
php artisan tinker

DB::enableQueryLog();
Course::with('chapters.lessons')->get();
dd(DB::getQueryLog());
```

---

## 🔄 Maintenance

### Mode maintenance

```bash
# Activer le mode maintenance
php artisan down

# Avec message personnalisé
php artisan down --message="Maintenance en cours" --retry=60

# Avec secret pour accès admin
php artisan down --secret="admin-access-token"
# Accès via: https://domain.com/admin-access-token

# Désactiver le mode maintenance
php artisan up
```

### Backup de la base de données

```bash
# Installer le package
composer require spatie/laravel-backup

# Publier la configuration
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Créer un backup
php artisan backup:run

# Backup uniquement la base de données
php artisan backup:run --only-db

# Lister les backups
php artisan backup:list

# Nettoyer les vieux backups
php artisan backup:clean
```

---

## 📝 Génération de Code

### Créer des ressources

```bash
# Créer un modèle avec migration et controller
php artisan make:model Product -mc

# Créer un modèle complet (migration, controller, factory, seeder)
php artisan make:model Product -mcfs

# Créer un controller avec ressources
php artisan make:controller ProductController --resource

# Créer une requête de validation
php artisan make:request StoreProductRequest

# Créer un middleware
php artisan make:middleware CheckSubscription

# Créer un job
php artisan make:job ProcessVideoUpload

# Créer une notification
php artisan make:notification OrderShipped

# Créer un event et listener
php artisan make:event OrderPlaced
php artisan make:listener SendOrderNotification
```

---

## 🎯 Workflows Utiles

### Setup complet d'un nouvel environnement

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve
```

### Déploiement en production

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan queue:restart
sudo systemctl restart php8.1-fpm
```

### Reset complet de l'environnement

```bash
php artisan down
php artisan optimize:clear
composer dump-autoload
php artisan migrate:fresh --seed
php artisan storage:link
php artisan optimize
php artisan up
```

---

## 🚀 Commandes Rapides pour Tester

### Créer des données de test rapidement

```bash
php artisan tinker

# Créer 10 utilisateurs étudiants
User::factory()->count(10)->create()->each(function($user) {
    $user->assignRole('student');
});

# Créer 5 formations
Course::factory()->count(5)->create([
    'instructor_id' => User::whereHas('roles', fn($q) => $q->where('slug', 'instructor'))->first()->id
]);
```

### Vérifier les routes

```bash
# Lister toutes les routes
php artisan route:list

# Filtrer les routes API
php artisan route:list --path=api

# Filtrer par méthode
php artisan route:list --method=POST

# Rechercher une route spécifique
php artisan route:list --name=courses
```

### Vérifier les permissions

```bash
php artisan tinker

# Voir toutes les permissions
Permission::all()->pluck('name', 'slug');

# Voir les permissions d'un rôle
Role::where('slug', 'instructor')->first()->permissions->pluck('name');

# Tester les permissions d'un utilisateur
$user = User::find(1);
$user->hasPermission('courses.create'); // true/false
```

---

## 💡 Tips & Astuces

### Alias utiles (à ajouter dans .bashrc ou .zshrc)

```bash
alias pa='php artisan'
alias pas='php artisan serve'
alias pam='php artisan migrate'
alias pamf='php artisan migrate:fresh --seed'
alias pat='php artisan tinker'
alias pacc='php artisan config:clear && php artisan cache:clear'
```

### Utilisation avec Docker

```bash
# Si vous utilisez Laravel Sail
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail artisan serve

# Alias recommandé
alias sail='./vendor/bin/sail'
```

---

## 📚 Ressources Supplémentaires

- [Documentation Laravel](https://laravel.com/docs)
- [Laravel API Documentation](https://laravel.com/api/10.x/)
- [Laracasts](https://laracasts.com)

---

**Bonne chance avec votre développement ! 🚀**