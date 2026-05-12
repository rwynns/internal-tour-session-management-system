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
| Recreation Admin | admin@example.com | password |
| Cashier | cashier@example.com | password |

---

## Tech Stack

### Backend
- **Laravel 13** — PHP framework
- **Laravel Fortify** — Authentication (login, register, 2FA)
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
- **Pest v4** — Testing framework (1800+ tests)
- **Laravel Pint** — PHP code formatter
- **ESLint + Prettier** — Frontend linting & formatting
- **GitHub Actions** — CI pipeline (tests + lint)

---

## Fitur

### Core
- **Authentication** — Login/register dengan Fortify, termasuk 2FA
- **Role-based access** — Recreation Admin dan Cashier dengan middleware terpisah
- **Attraction Management** — CRUD + activate/deactivate
- **Session Management** — CRUD + set kapasitas + ubah status + guard delete (tidak bisa hapus session dengan alokasi aktif)
- **Guest Allocation** — Allocate, cancel, move dengan pessimistic locking untuk race condition

### Business Rules
- Validasi kapasitas (tidak boleh melebihi max_capacity)
- Session inactive tidak bisa dipilih untuk alokasi
- Session yang sudah lewat tidak bisa menerima alokasi baru
- Reallocation atomic (occupancy kedua session diupdate dalam satu transaksi, lock order konsisten untuk mencegah deadlock)

### Bonus
- Dashboard occupancy (admin: overview hari ini, cashier: session board)
- Search/filtering (cashier: filter by attraction + search by guest name)
- Seeder/demo data (7 attractions, 60+ sessions, 70+ allocations realistis)
- Activity log (audit trail semua aksi CRUD dan alokasi)
- Table sorting/pagination (attractions & sessions)
- CI/CD (GitHub Actions: tests + lint)
- Clean UI/UX (Tailwind + shadcn, responsive, dark mode)

---

## Arsitektur & Keputusan Teknis

### Single Dashboard Route
`/dashboard` menampilkan halaman berbeda berdasarkan role user:
- Admin → overview occupancy + quick actions
- Cashier → session board dengan allocate/move/cancel

Alasan: satu entry point lebih simpel, tidak perlu redirect logic setelah login.

### Pessimistic Locking untuk Alokasi
Menggunakan `lockForUpdate()` dalam transaksi database untuk mencegah race condition ketika dua cashier mencoba mengalokasikan slot terakhir secara bersamaan.

### Atomic Move Operation
Saat memindahkan tamu, kedua session di-lock dalam urutan ID yang konsisten (ascending) untuk mencegah deadlock, lalu occupancy diupdate dalam satu transaksi.

### Activity Log
Service class `ActivityLogger` yang dipanggil dari controller setelah setiap mutasi. Sederhana, tidak menggunakan package external — cukup satu tabel dan satu model.

### Session Delete Guard
Session dengan alokasi tamu aktif tidak bisa dihapus. Ini mencegah data inconsistency dimana guest_allocations mereferensi session yang sudah tidak ada.

---

## Assumptions

1. **Sistem internal** — tidak ada public registration. User dibuat melalui seeder atau admin.
2. **Satu venue** — tidak ada multi-tenant. Semua data dalam satu konteks venue.
3. **Timezone tunggal** — semua waktu menggunakan timezone server (Asia/Jakarta).
4. **Pax = jumlah orang** — satu alokasi bisa untuk grup (pax > 1).
5. **Source bersifat free text** — tidak di-enum karena bisa bervariasi (walk-in, phone, travel-agent, online, dll).
6. **Session tidak recurring** — setiap session adalah slot individual, bukan template berulang.
7. **Cancel = soft status change** — alokasi yang dibatalkan tidak dihapus dari database, hanya status berubah ke 'cancelled'.

---

## Tradeoffs

1. **SQLite untuk development** — lebih mudah setup tanpa install MySQL/PostgreSQL. Production menggunakan MySQL di Railway.
2. **Tidak ada real-time update** — dashboard tidak auto-refresh. Cashier perlu reload halaman untuk melihat perubahan dari cashier lain. Bisa ditambahkan dengan Laravel Reverb/Pusher jika diperlukan.
3. **Activity log sederhana** — tidak menggunakan package seperti spatie/laravel-activitylog. Cukup untuk kebutuhan audit trail dasar tanpa menambah dependency.
4. **Tidak ada soft delete** — attraction dan session yang dihapus benar-benar hilang. Untuk production sebenarnya lebih baik soft delete, tapi untuk scope assessment ini cukup hard delete dengan guard.
5. **Frontend filtering client-side untuk cashier** — session grouping (active/past) dilakukan di frontend karena jumlah data terbatas (hanya hari ini ke depan).
6. **Tidak ada export/report** — bukan requirement, bisa ditambahkan jika diperlukan.

---

## AI Usage

Proyek ini dikembangkan dengan bantuan AI tools:

- **Kiro (Claude)** — digunakan untuk scaffolding awal, implementasi business logic, penulisan test, dan code review. AI membantu mempercepat implementasi tapi semua keputusan arsitektur dan teknis di-review dan dipahami sebelum diterima.

Penggunaan AI difokuskan pada:
- Generasi boilerplate code (migrations, factories, seeders)
- Implementasi business logic dengan edge case handling
- Penulisan test suite yang komprehensif
- Refactoring dan code organization
- Debugging dan fixing issues

Semua kode yang dihasilkan AI telah di-review, dipahami, dan disesuaikan sesuai kebutuhan. Kandidat mampu menjelaskan setiap keputusan teknis yang diambil.
