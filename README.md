# Internal Tour Session Management System

Website internal untuk manajemen sesi aktivitas wisata. Digunakan oleh tim operasional untuk mengatur jadwal sesi, menentukan kapasitas peserta, mengelola alokasi tamu onsite, dan memonitor ketersediaan sesi.

## Setup Instruction

### Prasyarat

- PHP 8.3+
- Composer
- Node.js 22+
- npm / pnpm

### Langkah Instalasi (Tanpa Docker)

```bash
# 1. Clone repository
git clone <repository-url>
cd internal-tour-session-management-system

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
php artisan key:generate

# 4. Buat database SQLite
touch database/database.sqlite

# 5. Jalankan migrasi dan seeder
php artisan migrate --seed

# 6. Build frontend assets
npm run build

# 7. Jalankan server
php artisan serve
```

Atau gunakan shortcut:

```bash
composer setup
composer dev
```

`composer dev` akan menjalankan Laravel server, queue worker, dan Vite dev server secara bersamaan.

### Langkah Instalasi (Dengan Docker)

Prasyarat: Docker dan Docker Compose terinstall.

```bash
# 1. Clone repository
git clone <repository-url>
cd internal-tour-session-management-system

# 2. Setup environment
cp .env.example .env
```

Edit `.env` dan sesuaikan konfigurasi database untuk Docker:

```env
APP_KEY=          # akan di-generate otomatis
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=tour_session
DB_USERNAME=tour_user
DB_PASSWORD=secret
```

```bash
# 3. Build dan jalankan container
docker compose up -d --build

# 4. Generate app key
docker compose exec app php artisan key:generate

# 5. Jalankan migrasi dan seeder
docker compose exec app php artisan migrate --seed

# 6. Build frontend assets
docker compose exec app npm install
docker compose exec app npm run build
```

Aplikasi berjalan di `http://localhost:8000`.

Untuk menghentikan container:

```bash
docker compose down
```

### Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Recreation Admin | recreation_adm@gmail.com | password |
| Cashier | cashier@gmail.com | password |

---

## Tech Stack

### Backend
- **Laravel 13** — PHP framework
- **Laravel Fortify** — Authentication (login)
- **Inertia.js v3** — Server-side routing dengan client-side rendering
- **Laravel Wayfinder** — Type-safe route generation untuk frontend
- **SQLite** (development) / **MySQL** (production)

### Frontend
- **React 19** — UI library
- **TypeScript** — Type safety
- **Tailwind CSS v4** — Utility-first styling
- **shadcn/ui** — Component library (Radix UI primitives)
- **Tabler Icons** — Icon set

### Testing & CI
- **Pest v4** — Testing framework (1800+ tests, 10900+ assertions)
- **Laravel Pint** — PHP code formatter
- **ESLint + Prettier** — Frontend linting & formatting
- **GitHub Actions** — CI pipeline (tests + lint)

---

## Fitur

### Core
- **Authentication** — Login dengan Fortify
- **Role-based access** — Recreation Admin dan Cashier dengan middleware terpisah
- **Attraction Management** — CRUD + activate/deactivate
- **Session Management** — CRUD + set kapasitas + ubah status + guard (session selesai dikunci dari edit/toggle, tidak bisa hapus session dengan alokasi aktif atau session selesai yang memiliki data tamu)
- **Guest Allocation** — Allocate, cancel, move dengan pessimistic locking untuk race condition
- Dashboard occupancy (admin: overview hari ini, cashier: session board)
- Search/filtering (cashier: filter by attraction + search by guest name)
- Seeder/demo data (7 attractions, 60+ sessions, 70+ allocations realistis)
- Activity log (audit trail semua aksi CRUD dan alokasi)
- Table sorting/pagination (attractions & sessions)
- CI/CD (GitHub Actions: tests + lint)
- Clean UI/UX (Tailwind + shadcn, responsive, dark mode)