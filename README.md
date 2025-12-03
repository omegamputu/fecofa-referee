# FECOFA Arbitrage Management System

Application officielle de gestion des arbitres, ligues, utilisateurs, rôles et permissions pour la Fédération Congolaise de Football Association (FECOFA).  
Développée avec **Laravel 12**, **Livewire Volt**, **Spatie Permissions** et **Laravel Fortify**.

---

## 📌 Fonctionnalités principales

### 🔐 Authentification & Sécurité
- Connexion via Laravel Fortify  
- Gestion des invitations d’utilisateurs par email  
- Réinitialisation du mot de passe  
- Protection multi-rôles (Owner, Administrator, Member, Viewer)  
- Authentification à deux facteurs (2FA) (QR Code + App authenticator)

### 👥 Gestion des utilisateurs
- Création d’utilisateurs par invitation
- Modification du nom, email et rôle
- Activation / Désactivation d’un compte
- Ré-envoi d’invitation
- Suppression sécurisée
- Pagination & recherche

### 🏛️ Gestion des ligues
- Ajout, modification et suppression des ligues
- Champs disponibles : nom, code, province, siège, email, téléphone
- Filtrage par recherche
- Pagination avancée

### ⚽ Gestion des arbitres
- Classification par catégories
- Attribution d’ID (ex: LIFKIN-001, LIFKAT-123)
- Gestion CRUD complète
- Export PDF (listes officielles)

### 🔒 Rôles & Permissions (Spatie)
- Création et mise à jour des rôles
- Attribution/Retrait de permissions
- Interface utilisateur pour gérer les rôles
- Support natif de `hasRole`, `can`, `middleware:permission`

### 🖥️ Tableau de bord moderne
- UI moderne et dark mode
- Composants Flux UI (navigation, badges, dropdown, modals)
- Widgets d’activité et statistiques

---

## 🧱 Architecture technique

- **Laravel 12+**
- **Livewire Volt** (composants UI réactifs)
- **Spatie Laravel-Permission** (RBAC)
- **Fortify** (authentification avancée)
- **TailwindCSS + Flux UI**
- **MySQL / MariaDB**

---

## 🗂️ Prérequis

- PHP 8.2+
- Composer
- MySQL / MariaDB
- Node.js 18+ et npm
- Git
- Serveur Linux (Ubuntu recommandé) ou WSL2

---

## 🚀 Installation locale

### 1. Cloner le dépôt
```bash
git clone https://github.com/your-org/your-repo.git
cd your-repo
