# 🎓 Training Platform API - Laravel

API complète pour une plateforme de formations en ligne avec gestion de cours, tests, paiements et dashboards.

## 📋 Table des matières

- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Migrations & Seeders](#migrations--seeders)
- [Utilisation](#utilisation)
- [Tests](#tests)
- [Déploiement](#déploiement)

---

## 🔧 Prérequis

- PHP >= 8.2
- Composer
- MySQL >= 8.0 ou PostgreSQL >= 13
- Redis (optionnel mais recommandé)
- Node.js & NPM (pour assets si nécessaire)

---

## 📦 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/your-repo/training-platform-api.git
cd training-platform-api
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Copier le fichier d'environnement

```bash
cp .env.example .env
```

### 4. Générer la clé d'application

```bash
php artisan key:generate
```

### 5. Configurer la base de données

Éditer le fichier `.env` :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=training_platform
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 6. Créer la base de données

```sql
CREATE DATABASE training_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## ⚙️ Configuration

### Configurer Stripe

```env
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx
```

**Créer un webhook Stripe :**
1. Aller sur https://dashboard.stripe.com/webhooks
2. Ajouter endpoint : `https://your-domain.com/api/webhooks/stripe`
3. Sélectionner événements : `payment_intent.succeeded`, `payment_intent.payment_failed`
4. Copier le secret dans `.env`

### Configurer PayPal

```env
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=xxxxx
PAYPAL_SECRET=xxxxx
```

**Obtenir les credentials PayPal :**
1. Créer compte sur https://developer.paypal.com
2. Créer une application
3. Copier Client ID et Secret

### Configurer Vimeo (optionnel)

```env
VIMEO_ACCESS_TOKEN=xxxxx
VIMEO_CLIENT_ID=xxxxx
VIMEO_CLIENT_SECRET=xxxxx
```

**Obtenir token Vimeo :**
1. Créer application sur https://developer.vimeo.com
2. Générer Personal Access Token
3. Permissions : `upload`, `video_files`

### Configurer YouTube (optionnel)

```env
YOUTUBE_API_KEY=xxxxx
```

**Obtenir API Key YouTube :**
1. Google Cloud Console : https://console.cloud.google.com
2. Activer YouTube Data API v3
3. Créer credentials (API Key)

### Configurer Email

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@trainingplatform.com
MAIL_FROM_NAME="Training Platform"
```

### Configurer Redis (recommandé)

```env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### Configurer le stockage

```env
FILESYSTEM_DISK=public
```

Pour utiliser S3 :

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=xxxxx
AWS_SECRET_ACCESS_KEY=xxxxx
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket.s3.amazonaws.com
```

---

## 🗄️ Migrations & Seeders

### Exécuter les migrations

```bash
php artisan migrate
```

### Exécuter les seeders

```bash
php artisan db:seed
```

Ou tout en une commande :

```bash
php artisan migrate:fresh --seed
```

### Comptes créés par défaut

Après seeding, ces comptes sont disponibles :

| Rôle | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@trainingplatform.com | password |
| Admin | admin@trainingplatform.com | password |
| Instructor | instructor@trainingplatform.com | password |
| Student | student@trainingplatform.com | password |

---

## 🚀 Utilisation

### Démarrer le serveur

```bash
php artisan serve
```

L'API sera accessible sur : `http://localhost:8000/api`

### Créer le lien symbolique pour le stockage

```bash
php artisan storage:link
```

### Démarrer les workers de queue (optionnel)

```bash
php artisan queue:work --tries=3
```

### Nettoyer le cache

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

### Optimiser pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📝 Utilisation de l'API

### 1. Inscription

```bash
POST /api/register
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response :**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "user": { ... },
    "access_token": "1|xxxxx",
    "token_type": "Bearer"
  }
}
```

### 2. Connexion

```bash
POST /api/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

### 3. Créer une formation

```bash
POST /api/courses
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Laravel Complete Guide",
  "description": "Learn Laravel from scratch",
  "category_id": 1,
  "level": "beginner",
  "language": "en",
  "is_free": false,
  "price": 49.99,
  "currency": "USD"
}
```

### 4. Ajouter un chapitre

```bash
POST /api/courses/{courseId}/chapters
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Introduction to Laravel",
  "description": "Getting started with Laravel",
  "order": 1,
  "is_free": true
}
```

### 5. Ajouter une leçon

```bash
POST /api/chapters/{chapterId}/lessons
Authorization: Bearer {token}
Content-Type: multipart/form-data

title: "Installing Laravel"
description: "How to install Laravel"
content_type: "video"
video_url: "https://youtube.com/watch?v=xxxxx"
video_provider: "youtube"
order: 1
duration_minutes: 15
```

### 6. Créer un test

```bash
POST /api/tests
Authorization: Bearer {token}
Content-Type: application/json

{
  "testable_type": "App\\Models\\Chapter",
  "testable_id": 1,
  "title": "Laravel Basics Quiz",
  "type": "formative",
  "position": "after_chapter",
  "duration_minutes": 30,
  "max_attempts": 3,
  "passing_score": 70,
  "validation_type": "automatic"
}
```

### 7. Ajouter des questions

```bash
POST /api/tests/{testId}/questions
Authorization: Bearer {token}
Content-Type: application/json

{
  "question_text": "What is Laravel?",
  "type": "single_choice",
  "points": 10,
  "order": 1,
  "options": [
    {
      "option_text": "A PHP Framework",
      "is_correct": true
    },
    {
      "option_text": "A JavaScript Library",
      "is_correct": false
    }
  ]
}
```

### 8. Passer un test

```bash
# Démarrer le test
POST /api/tests/{testId}/start
Authorization: Bearer {token}

# Soumettre les réponses
POST /api/submissions/{submissionId}/submit
Authorization: Bearer {token}
Content-Type: application/json

{
  "answers": [
    {
      "question_id": 1,
      "selected_options": [1]
    },
    {
      "question_id": 2,
      "answer_text": "Laravel is a PHP framework..."
    }
  ]
}
```

### 9. Effectuer un paiement

```bash
# Calculer le total
GET /api/payments/calculate/{courseId}?promo_code=SUMMER2024
Authorization: Bearer {token}

# Créer le paiement
POST /api/payments/create
Authorization: Bearer {token}
Content-Type: application/json

{
  "course_id": 1,
  "payment_gateway": "stripe",
  "promo_code": "SUMMER2024"
}

# Compléter le paiement (après Stripe)
POST /api/payments/{paymentId}/complete
Authorization: Bearer {token}
Content-Type: application/json

{
  "gateway_transaction_id": "pi_xxxxx"
}
```

### 10. Dashboard étudiant

```bash
# Vue d'ensemble
GET /api/student/dashboard/overview
Authorization: Bearer {token}

# Mes formations
GET /api/student/dashboard/enrollments
Authorization: Bearer {token}

# Mes certificats
GET /api/student/dashboard/certificates
Authorization: Bearer {token}
```

---

## 🧪 Tests

### Créer les tests

```bash
php artisan make:test CourseTest
php artisan make:test PaymentTest
```

### Exécuter les tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter CourseTest

# Avec couverture
php artisan test --coverage
```

---

## 🌐 Déploiement

### 1. Préparer le serveur

Installer les dépendances :
```bash
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-redis php8.1-mbstring php8.1-xml php8.1-curl
sudo apt install mysql-server redis-server nginx
```

### 2. Configurer Nginx

```nginx
server {
    listen 80;
    server_name api.trainingplatform.com;
    root /var/www/training-platform-api/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 3. Installer SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d api.trainingplatform.com
```

### 4. Configurer les permissions

```bash
sudo chown -R www-data:www-data /var/www/training-platform-api
sudo chmod -R 755 /var/www/training-platform-api/storage
sudo chmod -R 755 /var/www/training-platform-api/bootstrap/cache
```

### 5. Configurer Supervisor (pour les queues)

```bash
sudo apt install supervisor

# Créer config
sudo nano /etc/supervisor/conf.d/training-platform-worker.conf
```

```ini
[program:training-platform-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/training-platform-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=3
redirect_stderr=true
stdout_logfile=/var/www/training-platform-api/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start training-platform-worker:*
```

### 6. Configurer Cron (pour le scheduler)

```bash
crontab -e
```

Ajouter :
```
* * * * * cd /var/www/training-platform-api && php artisan schedule:run >> /dev/null 2>&1
```

### 7. Déployer avec Git

```bash
cd /var/www/training-platform-api
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.1-fpm
sudo supervisorctl restart training-platform-worker:*
```

---

## 📊 Monitoring

### Laravel Telescope (développement)

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Accès : `http://localhost:8000/telescope`

### Laravel Horizon (queues Redis)

```bash
composer require laravel/horizon
php artisan horizon:install
```

Accès : `http://localhost:8000/horizon`

---

## 🔒 Sécurité

### Rate Limiting

Les routes API sont protégées par rate limiting (60 requêtes/minute par défaut).

### CORS

Configurer dans `config/cors.php` selon vos besoins.

### Validation des fichiers

- Taille maximale : 50 MB
- Types autorisés : configurables par endpoint
- Scan antivirus recommandé en production

### Backup de la base de données

```bash
# Installer le package
composer require spatie/laravel-backup

# Configurer
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"

# Exécuter un backup
php artisan backup:run
```

---

## 📚 Documentation API complète

La documentation Postman est disponible dans `/docs/postman_collection.json`

### Importer dans Postman

1. Ouvrir Postman
2. Import > Upload Files
3. Sélectionner `postman_collection.json`
4. Configurer l'environnement avec votre URL et token

---

## 🤝 Support

Pour toute question ou problème :
- Email : support@trainingplatform.com
- Documentation : https://docs.trainingplatform.com
- Issues : https://github.com/your-repo/training-platform-api/issues

