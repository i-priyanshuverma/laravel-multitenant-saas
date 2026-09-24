# Laravel 11 Multi-Tenant SaaS Engine

[![CI Workflow](https://github.com/saas/laravel-multitenant-saas/actions/workflows/ci.yml/badge.svg)](https://github.com/saas/laravel-multitenant-saas/actions)
[![Laravel 11](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel)](https://laravel.com)
[![Vue 3](https://img.shields.io/badge/Vue.js-3.x-4FC08D?logo=vuedotjs)](https://vuejs.org)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-2.x-9553E9?logo=inertia)](https://inertiajs.com)
[![FilamentPHP](https://img.shields.io/badge/FilamentPHP-v3-FDAE4B?logo=filament)](https://filamentphp.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql)](https://www.postgresql.org)
[![PHPStan Level 8](https://img.shields.io/badge/PHPStan-Level%208-brightgreen)](https://phpstan.org)

An enterprise-grade, high-throughput Multi-tenant SaaS platform built with **Laravel 11**, **Vue 3**, **Inertia.js**, **FilamentPHP v3**, and **PostgreSQL**.

---

## 🚀 Key Architectural Features

- **Dynamic Tenant Resolution**: Subdomain (`tenant.saas.com`) and custom domain identification with automatic `X-Tenant` header fallback middleware.
- **Global Data Isolation**: Eloquent `TenantScoped` trait ensuring zero cross-tenant data leaks at the query builder level.
- **Vue 3 & Inertia.js Workspace**: Modern dark-mode dashboard, user onboarding, team roster management, and profile settings (Inertia v1.2 stack).
- **Stripe Subscription Billing Engine**: Integrated Stripe SDK supporting Free, Pro, and Enterprise tiers (monthly & annual billing cycles) with webhook handlers for invoice renewals and 7-day grace periods.
- **Plan Usage Limits & Quota Enforcement**: Tier-based seat and storage limits enforcement (`PlanLimitService`) with route middleware (`EnforcePlanLimits`) to protect resource creation (team invitations, member seats) with automated upgrade prompts, 80% & 100% threshold notifications (`PlanLimitThresholdNotification`), and scheduled limit scanner (`tenants:check-plan-limits`).
- **Account Termination & Soft Deletes**: Soft delete lifecycle support for tenant account offboarding and workspace restoration.
- **FilamentPHP v3 Super-Admin Panel**: Dedicated `/admin` dashboard featuring MRR metrics, active tenant stats, recent tenant onboarding tables, churn reporting widgets, and tenant impersonation mode.
- **Role-Based Access Control (RBAC)**: Powered by Spatie Permissions and team invitation tokens.
- **Production DevOps Setup**: Optimized Docker Compose stack (PHP 8.2-FPM, Nginx, PostgreSQL 16, Redis), Kubernetes deployment manifests, database query indexing, and GitHub Actions CI.

---

## 📊 Database Entity Model

See detailed [ER Diagram Documentation](docs/architecture/er_diagram.md).

```
   +------------------+         +------------------+
   |     Tenants      |<------->|     Domains      |
   +------------------+         +------------------+
   | - id (UUID)      |         | - id (UUID)      |
   | - name           |         | - domain         |
   | - slug           |         | - is_primary     |
   +--------+---------+         +------------------+
            |
            |                   +------------------+
            +------------------>|      Users       |
            |                   +------------------+
            |                   | - tenant_id      |
            |                   | - is_super_admin |
            |                   +------------------+
            |
            |                   +------------------+
            +------------------>|  Subscriptions   |
                                +------------------+
                                | - stripe_status  |
                                | - plan_id        |
                                +------------------+
```

---

## 🛠️ Local Development Setup

### 1. Prerequisites
- **PHP 8.2+** with `pdo_pgsql`, `mbstring`, `bcmath`, `intl`
- **Composer 2.x**
- **Node.js 18+** & **npm**
- **PostgreSQL 16** (or Docker)

### 2. Installation Steps

```bash
# Clone repository
git clone https://github.com/saas/laravel-multitenant-saas.git
cd laravel-multitenant-saas

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run database migrations and seed demo data
php artisan migrate:fresh --seed

# Build frontend assets
npm run build
```

### 3. Running via Docker Compose

```bash
docker-compose up -d
```
Access the application at `http://localhost:8080` or `http://acme.localhost:8080`.

---

## 🧪 Testing & Quality Assurance

```bash
# Run PHPUnit test suite (in parallel)
php artisan test --parallel

# Code style checking and formatting (Laravel Pint)
./vendor/bin/pint --test

# Run PHPStan static analysis
./vendor/bin/phpstan analyse --memory-limit=1G

# Build production assets
npm run build
```

---

## 🔑 Demo Login Credentials

| Role | Domain / URL | Email | Password |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `http://localhost:8080/admin` | `admin@saas.com` | `password` |
| **Acme Tenant Owner** | `http://acme.localhost:8080/login` | `owner@acme.com` | `password` |
| **Stark Tenant Owner** | `http://stark.localhost:8080/login` | `tony@stark.com` | `password` |

---

## 📄 License
This repository is open-sourced software licensed under the [MIT license](LICENSE).
