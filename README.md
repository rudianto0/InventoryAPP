# Inventory App

Aplikasi manajemen inventori (stock) berbasis web dengan sistem autentikasi, manajemen user, role, dan permission.

## Tech Stack

- **Laravel 12** (PHP 8.2+)
- **Bootstrap 5** + **Bootstrap Icons** (via Vite & SCSS)
- **MySQL** (database)
- **Laravel Breeze** (scaffolding autentikasi — login, register, forgot/reset password)

## Fitur

- Autentikasi (login, register, forgot & reset password)
- Manajemen **User** (CRUD + toggle status aktif/nonaktif)
- Manajemen **Role** (CRUD + attach permissions per role)
- **Permission** berbasis role (seeder-only, bukan dari UI)
- Middleware proteksi route: `permission:` dan `active`
- Blade directive `@hasPermission` untuk menampilkan/menyembunyikan menu di sidebar

## Prasyarat

- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL (atau database lain yang didukung Laravel)

---

## Cara Menjalankan Pertama Kali (Setelah Clone)

### 1. Clone & masuk ke folder project

```bash
git clone <url-repository> inventory-app
cd inventory-app
```

### 2. Install dependency

```bash
composer install
npm install
```

### 3. Buat file environment

```bash
cp .env.example .env
```

Lalu generate application key:

```bash
php artisan key:generate
```

### 4. Konfigurasi database

Buat database MySQL terlebih dahulu:

```sql
CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Kemudian atur koneksi database di file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_db
DB_USERNAME=root
DB_PASSWORD=
```

### 5. Jalankan migrasi & seeder

```bash
php artisan migrate
php artisan db:seed
```

> Saat development, bisa digabung sekaligus dengan `php artisan migrate:fresh --seed`.

Seeder akan membuat data awal: 18 permissions, 4 role, dan 1 user Super Admin (lihat bagian [Akun Login](#akun-login-seeder)).

### 6. Build asset frontend

Development (dengan hot reload):

```bash
npm run dev
```

Production:

```bash
npm run build
```

### 7. Jalankan server

```bash
php artisan serve
```

Buka browser di: <http://localhost:8000>

---

## Akun Login (Seeder)

Seeder `UserSeeder` membuat **1 user Super Admin**:

| Nama | Email | Password | Role |
|---|---|---|---|
| Super Admin | `admin@example.com` | `password` | Super Admin |

> **Catatan:** User baru yang melakukan registrasi sendiri (melalui halaman `/register`) otomatis mendapat role **Staff**.

---

## Daftar Role

| Role | Slug | Keterangan |
|---|---|---|
| Super Admin | `super-admin` | Akses penuh ke semua fitur (otomatis punya semua permission, termasuk permission baru) |
| Admin | `admin` | Mengelola user, stock-in, stock-out, dan stock |
| Staff | `staff` | Mengelola stock-in, stock-out, dan melihat stock |
| Manager | `manager` | Melihat stock-in, stock-out, stock, dan user |

### Mapping Role → Permission

| Role | Permissions |
|---|---|
| **Super Admin** | Semua permissions secara otomatis |
| **Admin** | Semua kecuali `users.delete` dan `roles.*` (13 permissions) |
| **Staff** | `stock-in.*`, `stock-out.*`, `stock.read` (9 permissions) |
| **Manager** | `stock-in.read`, `stock-out.read`, `stock.read`, `users.read` (4 permissions) |

---

## Daftar Permission (18 Permission)

| Module | Slug | Nama |
|---|---|---|
| users | `users.read` | View User |
| users | `users.create` | Create User |
| users | `users.update` | Update User |
| users | `users.delete` | Delete User |
| roles | `roles.read` | View Role |
| roles | `roles.create` | Create Role |
| roles | `roles.update` | Update Role |
| roles | `roles.delete` | Delete Role |
| stock-in | `stock-in.read` | View Stock In |
| stock-in | `stock-in.create` | Create Stock In |
| stock-in | `stock-in.update` | Update Stock In |
| stock-in | `stock-in.delete` | Delete Stock In |
| stock-out | `stock-out.read` | View Stock Out |
| stock-out | `stock-out.create` | Create Stock Out |
| stock-out | `stock-out.update` | Update Stock Out |
| stock-out | `stock-out.delete` | Delete Stock Out |
| stock | `stock.read` | View Stock |
| stock | `stock.update` | Update Stock |

> Permission hanya dikelola lewat `PermissionSeeder`, bukan dari UI. Jika menambah permission baru, tambahkan di seeder lalu jalankan ulang `php artisan db:seed --class=PermissionSeeder` (Super Admin otomatis mendapatkannya).

---

## Struktur Project (Ringkas)

```
app/
├── Models/            # User, Role, Permission
├── Http/
│   ├── Controllers/   # Auth (Breeze), UserController, RoleController
│   └── Middleware/    # CheckPermission, EnsureUserIsActive
├── Traits/            # HasRoles
└── Providers/
database/
├── migrations/
└── seeders/           # DatabaseSeeder, PermissionSeeder, RoleSeeder, UserSeeder
resources/
├── js/
├── sass/
└── views/             # layouts/, dashboard, auth/, users/, roles/
routes/
└── web.php
```

---

## Scripts Composer

Tersedia beberapa script bantu di `composer.json`:

| Perintah | Keterangan |
|---|---|
| `composer setup` | Install dependency, copy `.env`, generate key, migrate, install npm, build asset |
| `composer dev` | Menjalankan server + queue + pail + vite secara bersamaan |
| `composer test` | Menjalankan test suite |

---

## Catatan Tambahan

- **Middleware `active`**: user yang dinonaktifkan (`is_active = false`) otomatis di-logout dan diblokir di request berikutnya.
- **Middleware `permission:`**: contoh pemakaian `->middleware('permission:users.read')`.
- **Blade directive `@hasPermission('slug')`**: dipakai di sidebar untuk menampilkan menu sesuai permission user.
- **Forgot password** (development): set `MAIL_MAILER=log` di `.env` agar link reset password tampil di `storage/logs/laravel.log`.
- Dokumentasi perencanaan lengkap ada di folder `plan/` (`setup.md` dan `user-login.md`).

## License

[MIT license](https://opensource.org/licenses/MIT).
