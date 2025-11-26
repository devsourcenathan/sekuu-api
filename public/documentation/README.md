# 🧪 Guide de Test Complet - Training Platform API

## 📋 Table des matières

1. [Prérequis](#prérequis)
2. [Installation de Postman](#installation-postman)
3. [Scénarios de test](#scénarios-de-test)
4. [Tests automatisés](#tests-automatisés)
5. [Troubleshooting](#troubleshooting)

---

## ✅ Prérequis

### 1. Démarrer l'API

```bash
# Migrer la base de données
php artisan migrate:fresh --seed

# Démarrer le serveur
php artisan serve
```

L'API sera disponible sur : `http://localhost:8000/api`

### 2. Vérifier les comptes créés

Les seeders créent automatiquement ces comptes :

| Rôle | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@trainingplatform.com | password |
| Admin | admin@trainingplatform.com | password |
| Instructor | instructor@trainingplatform.com | password |
| Student | student@trainingplatform.com | password |

---

## 📥 Installation de Postman

### Option 1 : Importer la collection

1. **Télécharger** les fichiers :
   - `postman_collection.json` - Collection complète
   - `postman_environment.json` - Variables d'environnement

2. **Ouvrir Postman**

3. **Importer la collection** :
   - Clic sur `Import` (en haut à gauche)
   - Glisser-déposer `postman_collection.json`
   - Clic sur `Import`

4. **Importer l'environnement** :
   - Clic sur `Import`
   - Glisser-déposer `postman_environment.json`
   - Sélectionner l'environnement `Training Platform - Local` (coin supérieur droit)

### Option 2 : Configuration manuelle

Si vous préférez configurer manuellement :

1. Créer une nouvelle collection : `Training Platform API`
2. Ajouter variable d'environnement :
   - Nom : `base_url`
   - Valeur : `http://localhost:8000/api`
3. Ajouter variable : `token` (sera remplie automatiquement)

---

## 🎯 Scénarios de Test

### Scénario 1 : Workflow Complet Instructeur

#### Étape 1 : Connexion en tant qu'instructeur

```
POST {{base_url}}/login
Body:
{
  "email": "instructor@trainingplatform.com",
  "password": "password"
}
```

✅ **Vérifications** :
- Status: 200
- Réponse contient : `access_token`
- Token est automatiquement sauvegardé dans `{{token}}`

#### Étape 2 : Créer une formation

```
POST {{base_url}}/courses
Headers: Authorization: Bearer {{token}}
Body:
{
  "title": "Ma Formation Laravel",
  "description": "Formation complète Laravel",
  "category_id": 1,
  "level": "intermediate",
  "language": "fr",
  "is_free": false,
  "price": 49.99,
  "currency": "USD"
}
```

✅ **Vérifications** :
- Status: 201
- Réponse contient : `id`, `title`, `instructor_id`
- `course_id` est sauvegardé automatiquement

#### Étape 3 : Ajouter un chapitre

```
POST {{base_url}}/courses/{{course_id}}/chapters
Headers: Authorization: Bearer {{token}}
Body:
{
  "title": "Introduction",
  "description": "Chapitre d'introduction",
  "order": 1,
  "is_free": true
}
```

✅ **Vérifications** :
- Status: 201
- `chapter_id` est sauvegardé

#### Étape 4 : Ajouter une leçon vidéo

```
POST {{base_url}}/chapters/{{chapter_id}}/lessons
Headers: Authorization: Bearer {{token}}
Body:
{
  "title": "Introduction à Laravel",
  "content_type": "video",
  "video_url": "https://www.youtube.com/watch?v=VIDEO_ID",
  "video_provider": "youtube",
  "video_id": "VIDEO_ID",
  "order": 1,
  "duration_minutes": 15,
  "is_free": true,
  "is_preview": true
}
```

✅ **Vérifications** :
- Status: 201
- `lesson_id` est sauvegardé

#### Étape 5 : Ajouter une ressource

```
POST {{base_url}}/resources
Headers: Authorization: Bearer {{token}}
Body (form-data):
- resourceable_type: App\Models\Course
- resourceable_id: {{course_id}}
- title: Syllabus du cours
- file: [sélectionner un PDF]
- is_free: true
- is_downloadable: true
- order: 1
```

✅ **Vérifications** :
- Status: 201
- Fichier uploadé avec succès

#### Étape 6 : Créer un test

```
POST {{base_url}}/tests
Headers: Authorization: Bearer {{token}}
Body:
{
  "testable_type": "App\\Models\\Chapter",
  "testable_id": "{{chapter_id}}",
  "title": "Quiz d'introduction",
  "type": "formative",
  "position": "after_chapter",
  "duration_minutes": 30,
  "max_attempts": 3,
  "passing_score": 70,
  "validation_type": "automatic",
  "is_published": true
}
```

✅ **Vérifications** :
- Status: 201
- `test_id` est sauvegardé

#### Étape 7 : Ajouter des questions

```
POST {{base_url}}/tests/{{test_id}}/questions
Headers: Authorization: Bearer {{token}}
Body:
{
  "question_text": "Qu'est-ce que Laravel ?",
  "type": "single_choice",
  "points": 10,
  "order": 1,
  "options": [
    {
      "option_text": "Un framework PHP",
      "is_correct": true
    },
    {
      "option_text": "Une bibliothèque JavaScript",
      "is_correct": false
    }
  ]
}
```

✅ **Vérifications** :
- Status: 201
- Question créée avec options

#### Étape 8 : Publier la formation

```
POST {{base_url}}/courses/{{course_id}}/publish
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- `status` = "published"

---

### Scénario 2 : Workflow Complet Étudiant

#### Étape 1 : Inscription d'un nouvel étudiant

```
POST {{base_url}}/register
Body:
{
  "name": "Jean Étudiant",
  "email": "jean.etudiant@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "+237655443322"
}
```

✅ **Vérifications** :
- Status: 201
- Token reçu et sauvegardé
- Rôle = "student"

#### Étape 2 : Lister les formations disponibles

```
GET {{base_url}}/courses?level=intermediate&is_free=false
```

✅ **Vérifications** :
- Status: 200
- Liste des formations publiées
- Filtres appliqués correctement

#### Étape 3 : Voir les détails d'une formation

```
GET {{base_url}}/courses/{{course_id}}
```

✅ **Vérifications** :
- Status: 200
- Détails complets : chapitres, leçons, instructeur

#### Étape 4 : Calculer le prix avec promo

```
GET {{base_url}}/payments/calculate/{{course_id}}?promo_code=SUMMER2024
```

✅ **Vérifications** :
- Status: 200
- Calcul du discount correct
- Total, plateforme fee, instructor amount

#### Étape 5 : Initier le paiement (Stripe)

```
POST {{base_url}}/payments/create
Headers: Authorization: Bearer {{token}}
Body:
{
  "course_id": "{{course_id}}",
  "payment_gateway": "stripe",
  "promo_code": "SUMMER2024"
}
```

✅ **Vérifications** :
- Status: 201
- `payment_id` et `client_secret` reçus
- Redirection vers Stripe

#### Étape 6 : Compléter le paiement

```
POST {{base_url}}/payments/{{payment_id}}/complete
Headers: Authorization: Bearer {{token}}
Body:
{
  "gateway_transaction_id": "pi_xxxxxxxxxxxxx"
}
```

✅ **Vérifications** :
- Status: 200
- Payment status = "completed"
- Enrollment créé automatiquement

#### Étape 7 : Accéder à la formation

```
GET {{base_url}}/student/dashboard/enrollments
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Formation visible dans "Mes formations"
- Progression = 0%

#### Étape 8 : Visionner une leçon

```
GET {{base_url}}/chapters/{{chapter_id}}/lessons/{{lesson_id}}
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Accès autorisé
- Contenu de la leçon disponible

#### Étape 9 : Mettre à jour la progression

```
POST {{base_url}}/chapters/{{chapter_id}}/lessons/{{lesson_id}}/progress
Headers: Authorization: Bearer {{token}}
Body:
{
  "progress_percentage": 75,
  "watch_time_seconds": 675
}
```

✅ **Vérifications** :
- Status: 200
- Progression enregistrée

#### Étape 10 : Marquer la leçon comme complétée

```
POST {{base_url}}/chapters/{{chapter_id}}/lessons/{{lesson_id}}/complete
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- `is_completed` = true
- Progression globale mise à jour

#### Étape 11 : Passer le test

```
POST {{base_url}}/tests/{{test_id}}/start
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- `submission_id` reçu
- Questions chargées

#### Étape 12 : Soumettre les réponses

```
POST {{base_url}}/submissions/{{submission_id}}/submit
Headers: Authorization: Bearer {{token}}
Body:
{
  "answers": [
    {
      "question_id": 1,
      "selected_options": [1]
    },
    {
      "question_id": 2,
      "answer_text": "Ma réponse..."
    }
  ]
}
```

✅ **Vérifications** :
- Status: 200
- Score calculé automatiquement
- `passed` = true/false

#### Étape 13 : Télécharger une ressource

```
GET {{base_url}}/resources/{{resource_id}}/download
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- URL de téléchargement retournée
- Compteur incrémenté

#### Étape 14 : Voir mon dashboard

```
GET {{base_url}}/student/dashboard/overview
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Statistiques à jour
- Formations en cours, complétées

#### Étape 15 : Télécharger le certificat

```
GET {{base_url}}/student/dashboard/certificates
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Certificat disponible si formation complétée

---

### Scénario 3 : Workflow Admin

#### Étape 1 : Connexion admin

```
POST {{base_url}}/login
Body:
{
  "email": "admin@trainingplatform.com",
  "password": "password"
}
```

#### Étape 2 : Vue d'ensemble de la plateforme

```
GET {{base_url}}/admin/dashboard/overview
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- KPIs : total users, courses, revenue
- Graphiques de croissance

#### Étape 3 : Gérer les utilisateurs

```
GET {{base_url}}/admin/dashboard/users?role=instructor&search=john
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Liste filtrée des utilisateurs

#### Étape 4 : Modérer les formations

```
GET {{base_url}}/admin/dashboard/courses?status=pending
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Formations en attente de validation

#### Étape 5 : Voir les paiements

```
GET {{base_url}}/admin/dashboard/payments?status=completed
Headers: Authorization: Bearer {{token}}
```

✅ **Vérifications** :
- Status: 200
- Liste des transactions

---

## 🤖 Tests Automatisés

### Tests avec Postman Runner

1. **Sélectionner la collection** `Training Platform API`
2. Clic sur **"Run collection"**
3. Sélectionner les dossiers à tester
4. Clic sur **"Run Training Platform API"**

Les tests s'exécuteront en séquence avec :
- Sauvegarde automatique des IDs
- Vérifications des status codes
- Validation des réponses

### Tests avec Newman (CLI)

```bash
# Installer Newman
npm install -g newman

# Exécuter la collection
newman run postman_collection.json -e postman_environment.json

# Avec rapport HTML
newman run postman_collection.json -e postman_environment.json -r html
```

---

## ⚠️ Troubleshooting

### Erreur 401 - Unauthenticated

**Cause** : Token expiré ou invalide

**Solution** :
1. Refaire la connexion avec `/login`
2. Vérifier que le token est bien dans l'environnement
3. Vérifier le header : `Authorization: Bearer {{token}}`

### Erreur 403 - Forbidden

**Cause** : Permissions insuffisantes

**Solution** :
1. Vérifier le rôle de l'utilisateur connecté
2. Se connecter avec le bon compte (instructor, admin, etc.)

### Erreur 404 - Not Found

**Cause** : Ressource inexistante ou ID incorrect

**Solution** :
1. Vérifier que les IDs sont bien sauvegardés dans les variables
2. Refaire le workflow depuis le début
3. Vérifier dans la BDD que la ressource existe

### Erreur 422 - Validation Error

**Cause** : Données invalides

**Solution** :
1. Lire le message d'erreur dans `errors`
2. Corriger les champs invalides
3. Vérifier les types de données (string, integer, etc.)

### Erreur 500 - Server Error

**Cause** : Erreur serveur

**Solution** :
1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier la configuration (base de données, etc.)
3. Relancer les migrations si nécessaire

### La collection ne fonctionne pas

**Solution** :
1. Vérifier que l'API tourne : `php artisan serve`
2. Vérifier l'URL : `http://localhost:8000/api`
3. Vérifier l'environnement Postman sélectionné
4. Réimporter la collection

---

## 📊 Checklist de Test Complet

### ✅ Authentification
- [ ] Inscription nouvel utilisateur
- [ ] Connexion utilisateur existant
- [ ] Récupération mot de passe
- [ ] Reset mot de passe
- [ ] Obtenir profil utilisateur
- [ ] Déconnexion

### ✅ Formations
- [ ] Créer une formation
- [ ] Lister les formations (avec filtres)
- [ ] Voir détails formation
- [ ] Mettre à jour formation
- [ ] Publier formation
- [ ] S'inscrire à une formation
- [ ] Supprimer formation

### ✅ Chapitres
- [ ] Créer chapitre
- [ ] Lister chapitres
- [ ] Mettre à jour chapitre
- [ ] Supprimer chapitre

### ✅ Leçons
- [ ] Créer leçon vidéo
- [ ] Créer leçon texte
- [ ] Créer leçon PDF
- [ ] Mettre à jour progression
- [ ] Marquer comme complétée
- [ ] Supprimer leçon

### ✅ Ressources
- [ ] Uploader ressource
- [ ] Télécharger ressource
- [ ] Supprimer ressource

### ✅ Tests
- [ ] Créer test
- [ ] Ajouter question QCM
- [ ] Ajouter question texte
- [ ] Ajouter question vrai/faux
- [ ] Démarrer test
- [ ] Sauvegarder brouillon
- [ ] Soumettre test
- [ ] Noter manuellement
- [ ] Voir mes soumissions
- [ ] Voir tests à corriger

### ✅ Paiements
- [ ] Calculer total avec promo
- [ ] Créer paiement Stripe
- [ ] Créer paiement PayPal
- [ ] Compléter paiement
- [ ] Voir mes paiements
- [ ] Demander remboursement

### ✅ Dashboards
- [ ] Dashboard étudiant - overview
- [ ] Dashboard étudiant - enrollments
- [ ] Dashboard étudiant - certificats
- [ ] Dashboard instructeur - overview
- [ ] Dashboard instructeur - analytics
- [ ] Dashboard instructeur - revenus
- [ ] Dashboard admin - overview
- [ ] Dashboard admin - users
- [ ] Dashboard admin - payments

---

## 🎯 Résultats Attendus

Après avoir exécuté tous les tests, vous devriez avoir :

✅ **100% des endpoints fonctionnels**
✅ **Toutes les permissions vérifiées**
✅ **Workflow complet testé de bout en bout**
✅ **Gestion des erreurs validée**
✅ **Intégrations paiements testées**

---

## 📞 Support

En cas de problème :
1. Vérifier ce guide
2. Consulter les logs Laravel
3. Vérifier la documentation API
4. Ouvrir une issue sur GitHub

**Bon testing ! 🚀**