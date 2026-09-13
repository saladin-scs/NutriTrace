# NutriTrace

Plateforme **Food Traceability + Environmental Intelligence + Smart Waste Management** (Laravel 12).

Zone pilote : Grand Tunis.

## Documentation

- [`CAHIER_DES_CHARGES.md`](CAHIER_DES_CHARGES.md) — cadrage produit
- [`docs/COLD_ROOMS.md`](docs/COLD_ROOMS.md) — chambres froides **et** traçabilité des flux
- [`docs/BLADE_TEMPLATES.md`](docs/BLADE_TEMPLATES.md) — Front office + Back office Blade
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) — couches Domain / Application / Http
- [`docs/DATA_MODEL.md`](docs/DATA_MODEL.md) — schéma relationnel Sprint 1

## Stack

| Couche | Techno |
|--------|--------|
| Backend | PHP 8.2+, Laravel 12 |
| Auth | Breeze + Sanctum |
| Frontend MVP | Blade, Alpine.js, Tailwind, Vite |
| BDD | SQLite (dev) → MySQL/PostgreSQL (prod) |

## Sprint 1 (livré)

- Architecture Domain, migrations, modèles Eloquent, taxonomies seedées

## Sprint 2 (livré)

- Organisations (CRUD front + admin), memberships, validation admin
- Utilisateurs admin + assignation rôles plateforme
- Policies RBAC, audit log des actions critiques
- API `/api/v1/organizations`

## Sprint 3 (livré)

- Produits & lots (codes `NT-YYYY-NNNNNN`)
- Transformation lot entrant → lot sortant
- Distribution + événements de traçabilité
- Passeport public QR : `/trace/{code}`
- API produits / lots / traçabilité

## Installation

```bash
composer install
cp .env.example .env   # si besoin
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install && npm run build
php artisan serve
```

Accès : http://127.0.0.1:8000

## Comptes démo

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Admin | `admin@nutritrace.test` | `password` |
| Consommateur | `user@nutritrace.test` | `password` |
| Producteur | `producer@nutritrace.test` | `password` |
| Transformateur | `transformer@nutritrace.test` | `password` |
| Commerçant | `retailer@nutritrace.test` | `password` |
| Collecteur | `collector@nutritrace.test` | `password` |

Passeport démo (après seed) : `/batches` puis lien « Passeport public », ou `/trace/NT-…`.
