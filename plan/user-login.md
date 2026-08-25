# Plan: Manajemen User, Role, Permission & Autentikasi

> **Stack:** Laravel 12+ + Bootstrap 5  
> **Terkait:** Setup project → [`setup.md`](./setup.md)  
> **Tujuan:** Autentikasi + manajemen user dengan multi-role & role-based permissions.

---

## 1. Database Schema

### 1.1 Modifikasi Tabel `users`
Migration Breeze sudah ada di `database/migrations/xxxx_create_users_table.php`.  
**Tambahkan kolom** `is_active`:
```php
$table->boolean(''is_active'')->default(true)->after(''password'');
```

### 1.2 Buat Migration: `create_roles_table`
```bash
php artisan make:migration create_roles_table
```
```php
Schema::create(''roles'', function (Blueprint $table) {
    $table->id();
    $table->string(''name'', 100);
    $table->string(''slug'', 100)->unique();
    $table->text(''description'')->nullable();
    $table->timestamps();
});
```

### 1.3 Buat Migration: `create_permissions_table`
```bash
php artisan make:migration create_permissions_table
```
```php
Schema::create(''permissions'', function (Blueprint $table) {
    $table->id();
    $table->string(''name'', 100);
    $table->string(''slug'', 100)->unique();
    $table->string(''module'', 50);
    $table->text(''description'')->nullable();
    $table->timestamps();
});
```

### 1.4 Buat Migration: `create_role_user_table` (pivot)
```bash
php artisan make:migration create_role_user_table
```
```php
Schema::create(''role_user'', function (Blueprint $table) {
    $table->foreignId(''user_id'')->constrained()->cascadeOnDelete();
    $table->foreignId(''role_id'')->constrained()->cascadeOnDelete();
    $table->primary([''user_id'', ''role_id'']);
});
```

### 1.5 Buat Migration: `create_permission_role_table` (pivot)
```bash
php artisan make:migration create_permission_role_table
```
```php
Schema::create(''permission_role'', function (Blueprint $table) {
    $table->foreignId(''role_id'')->constrained()->cascadeOnDelete();
    $table->foreignId(''permission_id'')->constrained()->cascadeOnDelete();
    $table->primary([''role_id'', ''permission_id'']);
});
```

### ER Diagram
```
users ──< role_user >── roles ──< permission_role >── permissions
```

---

## 2. Model & Relations

### 2.1 Model `User` (`app/Models/User.php`)
Modifikasi model User (dari Breeze), tambahkan `HasRoles` trait & cast `is_active`:
```php
use App\Traits\HasRoles;

class User extends Authenticatable
{
    use HasRoles;

    protected $fillable = [
        ''name'', ''email'', ''password'', ''is_active'',
    ];

    protected $casts = [
        ''email_verified_at'' => ''datetime'',
        ''password'' => ''hashed'',
        ''is_active'' => ''boolean'',
    ];
}
```

### 2.2 Buat Model `Role`
```bash
php artisan make:model Role
```
```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\User;
use App\Models\Permission;

class Role extends Model
{
    protected $fillable = [''name'', ''slug'', ''description''];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }
}
```

### 2.3 Buat Model `Permission`
```bash
php artisan make:model Permission
```
```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Role;

class Permission extends Model
{
    protected $fillable = [''name'', ''slug'', ''module'', ''description''];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

### 2.4 Buat Trait `HasRoles` (`app/Traits/HasRoles.php`)
```php
namespace App\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Role::class);
    }

    public function hasRole(string $slug): bool
    {
        return $this->roles->contains(''slug'', $slug);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(''super-admin'');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->getAllPermissions()->contains(''slug'', $slug);
    }

    public function getAllPermissions()
    {
        if ($this->isSuperAdmin()) {
            return \App\Models\Permission::all();
        }

        return cache()->remember("user_{$this->id}_permissions", 3600, function () {
            return $this->roles()
                ->with(''permissions'')
                ->get()
                ->pluck(''permissions'')
                ->flatten()
                ->unique(''id'');
        });
    }

    public function clearPermissionCache(): void
    {
        cache()->forget("user_{$this->id}_permissions");
    }
}
```

---

## 3. Middleware & Blade Directive

### 3.1 Buat Middleware `CheckPermission`
```bash
php artisan make:middleware CheckPermission
```
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): mixed
    {
        if (!$request->user() || !$request->user()->hasPermission($permission)) {
            abort(403, ''Anda tidak memiliki izin.'');
        }
        return $next($request);
    }
}
```

### 3.2 Register Middleware Alias (`bootstrap/app.php`)
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        ''permission'' => \App\Http\Middleware\CheckPermission::class,
        ''active'' => \App\Http\Middleware\EnsureUserIsActive::class,
    ]);
})
```

### 3.3 Blade Directive `@hasPermission` (`app/Providers/AppServiceProvider.php`)
```php
use Illuminate\Support\Facades\Blade;

public function boot(): void
{
    Blade::directive(''hasPermission'', function (string $expression) {
        return "<?php if(auth()->check() && auth()->user()->hasPermission({$expression})): ?>";
    });

    Blade::directive(''endhasPermission'', function () {
        return "<?php endif; ?>";
    });
}
```

### 3.4 Buat Middleware `EnsureUserIsActive`
```bash
php artisan make:middleware EnsureUserIsActive
```
```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && !$request->user()->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route(''login'')->withErrors([
                ''email'' => ''Akun Anda dinonaktifkan.'',
            ]);
        }

        return $next($request);
    }
}
```
> **Pemakaian:** Terapkan middleware `active` pada route group `auth` (section 11) agar user yang di-deactivate saat sedang login langsung diblokir di request berikutnya.

---

## 4. Seeders (Data Awal)

### 4.1 `PermissionSeeder`
```bash
php artisan make:seeder PermissionSeeder
```
Seed 18 permission (lihat section 6 — Daftar Permission).

> **Catatan:** Permission **tidak** dikelola melalui UI. Permission hanya bisa ditambah/diubah/dihapus melalui seeder ini. Jika di kemudian hari ada permission baru, tambahkan datanya di `PermissionSeeder` lalu jalankan ulang seeder (Role Super Admin otomatis mendapatkannya — lihat section 7).

### 4.2 `RoleSeeder`
```bash
php artisan make:seeder RoleSeeder
```
Seed 4 default role:
- Super Admin (slug: `super-admin`)
- Admin (slug: `admin`)
- Staff (slug: `staff`)
- Manager (slug: `manager`)

Role Super Admin **tidak** perlu di-attach permission karena otomatis mendapatkan semua permission (lihat section 7). Role lain di-attach permission sesuai mapping (lihat section 7).

### 4.3 `UserSeeder`
```bash
php artisan make:seeder UserSeeder
```
Buat 1 Super Admin:
- **Email:** `admin@example.com`
- **Password:** `password`
- **Role:** Super Admin

### 4.4 `DatabaseSeeder` (Orchestration)
Urutan pemanggilan harus benar (permission → role → user):
```php
public function run(): void
{
    $this->call([
        PermissionSeeder::class,
        RoleSeeder::class,
        UserSeeder::class,
    ]);
}
```
> **Catatan:** `RoleSeeder` butuh permission (untuk attach ke role non-super-admin), dan `UserSeeder` butuh role (untuk attach Super Admin). Jadi urutan tidak boleh dibalik.

---

## 5. Fitur Autentikasi

### 5.1 Halaman Login
- **Route:** `GET /login` & `POST /login` → `AuthenticatedSessionController@create` / `@store`
- **UI:** Form Bootstrap: email + password, tombol "Login", link "Lupa Password?"
- **Validasi:** Email wajib & format valid, password wajib & minimal 6 karakter
- **Logic:** Sebelum autentikasi, cek `is_active = true`. Jika `false`, batalkan login dan tampilkan pesan "Akun dinonaktifkan". Implementasi di `AuthenticatedSessionController@store` (atau `LoginRequest@authenticate`).
- **Cache:** Setelah login sukses, permissions user diambil on-demand & di-cache oleh `getAllPermissions()` (lihat section 2.4). Tidak perlu simpan ke session.
- **Rate Limiting:** Maksimal 5 percobaan login per menit, via `LoginRequest` (Breeze/Fortify) — method `ensureIsNotRateLimited()`.

### 5.2 Halaman Register
- **Route:** `GET /register` & `POST /register`
- **UI:** Form Bootstrap: name, email, password, confirm password
- **Default Role:** User baru otomatis mendapat default role `staff` (slug: `staff`). Attach role ini di `RegisteredUserController@store`.
- **Validasi:** Email unik, password minimal 6 karakter & konfirmasi cocok

### 5.3 Logout
- **Route:** `POST /logout` → `AuthenticatedSessionController@destroy`
- **Logic:** Hapus session + panggil `clearPermissionCache()`, lalu redirect ke halaman login

### 5.4 Forgot / Reset Password
- **Route:**
  - `GET /forgot-password` & `POST /forgot-password` → `PasswordResetLinkController`
  - `GET /reset-password/{token}` & `POST /reset-password` → `NewPasswordController`
- **Views:** `auth/forgot-password.blade.php` + `auth/reset-password.blade.php`
- **Logic:** Kirim email reset link via Laravel Mail.
- **Konfigurasi Mail (.env):** Set `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`. Untuk dev bisa pakai `MAIL_MAILER=log` agar link reset tampil di `storage/logs/laravel.log`.

---

## 6. Daftar Permission (Awal — 18 Permission)

| # | Module | Slug | Nama |
|---|---|---|---|
| 1 | `users` | `users.read` | View User |
| 2 | `users` | `users.create` | Create User |
| 3 | `users` | `users.update` | Update User |
| 4 | `users` | `users.delete` | Delete User |
| 5 | `roles` | `roles.read` | View Role |
| 6 | `roles` | `roles.create` | Create Role |
| 7 | `roles` | `roles.update` | Update Role |
| 8 | `roles` | `roles.delete` | Delete Role |
| 9 | `stock-in` | `stock-in.read` | View Stock In |
| 10 | `stock-in` | `stock-in.create` | Create Stock In |
| 11 | `stock-in` | `stock-in.update` | Update Stock In |
| 12 | `stock-in` | `stock-in.delete` | Delete Stock In |
| 13 | `stock-out` | `stock-out.read` | View Stock Out |
| 14 | `stock-out` | `stock-out.create` | Create Stock Out |
| 15 | `stock-out` | `stock-out.update` | Update Stock Out |
| 16 | `stock-out` | `stock-out.delete` | Delete Stock Out |
| 17 | `stock` | `stock.read` | View Stock |
| 18 | `stock` | `stock.update` | Update Stock |

---

## 7. Default Roles & Permission Mapping

| Role | Permissions |
|---|---|
| **Super Admin** | ✅ Semua permissions secara otomatis (termasuk permission baru yang ditambahkan kemudian) |
| **Admin** | Semua kecuali: `users.delete`, `roles.*` (13 permissions) |
| **Staff** | `stock-in.*`, `stock-out.*`, `stock.read` (9 permissions) |
| **Manager** | `stock-in.read`, `stock-out.read`, `stock.read`, `users.read` (4 permissions) |

> **Super Admin:** Permission tidak di-attach di seeder. Method `hasPermission()` selalu mengembalikan `true` untuk role Super Admin, sehingga permission baru yang ditambahkan di kemudian hari otomatis dimiliki Super Admin tanpa perubahan apa pun.

---

## 8. Fitur Manajemen User (CRUD)

> Permission: `users.*`

### List User — `GET /users` → `users.read`
Tabel Bootstrap: #, Nama, Email, Status (badge), Roles (badge), Aksi. Fitur search, pagination, filter.

### Create User — `GET /users/create` & `POST /users` → `users.create`
Form Bootstrap: nama, email, password, status (toggle), roles (checkbox group).

### Edit User — `GET /users/{id}/edit` & `PUT /users/{id}` → `users.update`
Form seperti create, pre-filled. Password opsional.

### Delete User — `DELETE /users/{id}` → `users.delete`
Soft-deactivate: set `is_active = false` (tidak hapus permanen).

### Toggle Status — `PUT /users/{id}/toggle` → `users.update`
Toggle `is_active`. Panggil `$user->clearPermissionCache()` jika role ikut berubah.

### `UserController`
```
index()       → List + search + pagination
create()      → Form create
store()       → Simpan user + attach roles
edit($id)     → Form edit
update($id)   → Update user + sync roles
destroy($id)  → Deactivate user
toggle($id)   → Toggle is_active
```

### Views
```
resources/views/users/
├── index.blade.php
├── create.blade.php
└── edit.blade.php
```

> **Catatan form:** Semua form memakai `@csrf`. Form edit memakai `@method('PUT')`, form delete memakai `@method('DELETE')`. Saat `update()` / `toggle()` mengubah role user, panggil `$user->clearPermissionCache()`.

---

## 9. Fitur Manajemen Role

> Permission: `roles.*`

### List Role — `GET /roles` → `roles.read`
Tabel: #, Nama, Slug, Jumlah User, Aksi.

### Create Role — `GET /roles/create` & `POST /roles` → `roles.create`
Form: nama, slug (auto-generate via `Str::slug($request->name)`), deskripsi, checklist permissions grouped by module.

### Edit Role — `GET /roles/{id}/edit` & `PUT /roles/{id}` → `roles.update`
Sama seperti create, pre-filled. Slug tidak bisa diubah.

### Delete Role — `DELETE /roles/{id}` → `roles.delete`
Hapus role + relasi pivot. Cegah hapus jika ada user yang hanya punya role ini.

### `RoleController`
```
index()       → List + count users per role
create()      → Form create + permissions grouped by module
store()       → Simpan role + attach permissions
edit($id)     → Form edit
update($id)   → Update role + sync permissions
destroy($id)  → Validasi user, lalu delete
```

### Views
```
resources/views/roles/
├── index.blade.php
├── create.blade.php
└── edit.blade.php
```

> **Catatan:** Saat `update()` / `destroy()` role, panggil `clearPermissionCache()` untuk semua user yang memakai role tersebut. Form edit memakai `@method('PUT')`.

---

## 10. Manajemen Permission (Seeder Only)

> Permission **tidak** dikelola melalui UI (tidak ada CRUD permission).

Permission hanya dikelola melalui seeder khusus, yaitu `PermissionSeeder`. Jika di kemudian hari dibutuhkan permission baru:

1. Tambahkan data permission di `PermissionSeeder`.
2. Jalankan `php artisan db:seed --class=PermissionSeeder` (atau `php artisan migrate:fresh --seed` saat development).
3. Role **Super Admin** otomatis mendapatkan permission baru tersebut (lihat section 7).
4. Untuk role lain, attach permission baru sesuai kebutuhan (bisa di `RoleSeeder` atau langsung di DB).

Tidak ada `PermissionController`, `PermissionRequest`, maupun views `resources/views/permissions/`.

---

## 11. Routes (`routes/web.php`)

```php
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

// Guest
Route::middleware(''guest'')->group(function () {
    Route::get(''login'', [AuthenticatedSessionController::class, ''create''])->name(''login'');
    Route::post(''login'', [AuthenticatedSessionController::class, ''store'']);

    Route::get(''register'', [RegisteredUserController::class, ''create''])->name(''register'');
    Route::post(''register'', [RegisteredUserController::class, ''store'']);

    Route::get(''forgot-password'', [PasswordResetLinkController::class, ''create''])->name(''password.request'');
    Route::post(''forgot-password'', [PasswordResetLinkController::class, ''store''])->name(''password.email'');

    Route::get(''reset-password/{token}'', [NewPasswordController::class, ''create''])->name(''password.reset'');
    Route::post(''reset-password'', [NewPasswordController::class, ''store''])->name(''password.store'');
});

// Auth
Route::middleware([''auth'', ''active''])->group(function () {
    Route::post(''logout'', [AuthenticatedSessionController::class, ''destroy''])->name(''logout'');
    Route::get(''dashboard'', fn () => view(''dashboard''))->name(''dashboard'');

    // Users
    Route::get(''users'', [UserController::class, ''index''])->middleware(''permission:users.read'')->name(''users.index'');
    Route::get(''users/create'', [UserController::class, ''create''])->middleware(''permission:users.create'')->name(''users.create'');
    Route::post(''users'', [UserController::class, ''store''])->middleware(''permission:users.create'')->name(''users.store'');
    Route::get(''users/{id}/edit'', [UserController::class, ''edit''])->middleware(''permission:users.update'')->name(''users.edit'');
    Route::put(''users/{id}'', [UserController::class, ''update''])->middleware(''permission:users.update'')->name(''users.update'');
    Route::delete(''users/{id}'', [UserController::class, ''destroy''])->middleware(''permission:users.delete'')->name(''users.destroy'');
    Route::put(''users/{id}/toggle'', [UserController::class, ''toggle''])->middleware(''permission:users.update'')->name(''users.toggle'');

    // Roles
    Route::get(''roles'', [RoleController::class, ''index''])->middleware(''permission:roles.read'')->name(''roles.index'');
    Route::get(''roles/create'', [RoleController::class, ''create''])->middleware(''permission:roles.create'')->name(''roles.create'');
    Route::post(''roles'', [RoleController::class, ''store''])->middleware(''permission:roles.create'')->name(''roles.store'');
    Route::get(''roles/{id}/edit'', [RoleController::class, ''edit''])->middleware(''permission:roles.update'')->name(''roles.edit'');
    Route::put(''roles/{id}'', [RoleController::class, ''update''])->middleware(''permission:roles.update'')->name(''roles.update'');
    Route::delete(''roles/{id}'', [RoleController::class, ''destroy''])->middleware(''permission:roles.delete'')->name(''roles.destroy'');
});
```

> **Catatan:** Breeze (Laravel 11/12) menaruh route auth di `routes/auth.php` yang di-include dari `bootstrap/app.php`. Boleh disatukan ke `web.php` di atas, atau tetap di `routes/auth.php` lalu tambahkan route Users & Roles di `web.php`.

---

## 12. Form Requests (Validasi)

| Request Class | Aturan Validasi |
|---|---|
| `UserRequest` | `name` wajib, `email` wajib + unik, `password` wajib (create) / opsional (update), `is_active` boolean, `roles` array (nullable) |
| `RoleRequest` | `name` wajib, `slug` wajib + unik (hanya create), `permissions` array (nullable) |

---

## 13. Layout & Sidebar Menu (Dynamic by Permission)

Layout utama di `resources/views/layouts/app.blade.php` menggunakan Bootstrap 5 dengan:
- **Sidebar kiri** — menu tampil/sembunyi berdasarkan permission via `@hasPermission`
- **Navbar atas** — toggle sidebar, user dropdown, logout
- **Content area** — `@yield(''content'')`

Contoh sidebar:
```blade
<li class="nav-item">
    <a href="/dashboard" class="nav-link">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>
</li>

@hasPermission(''users.read'')
<li class="nav-item">
    <a href="/users" class="nav-link">
        <i class="bi bi-people"></i> Manajemen User
    </a>
</li>
@endhasPermission

@hasPermission(''roles.read'')
<li class="nav-item">
    <a href="/roles" class="nav-link">
        <i class="bi bi-shield-lock"></i> Manajemen Role
    </a>
</li>
@endhasPermission
```

---

## 14. Struktur File (User Management Only)

```
app/
├── Models/
│   ├── User.php
│   ├── Role.php
│   └── Permission.php
├── Http/
│   ├── Controllers/
│   │   ├── Auth/                       (dari Breeze — dimodifikasi)
│   │   │   ├── AuthenticatedSessionController.php
│   │   │   ├── RegisteredUserController.php
│   │   │   ├── PasswordResetLinkController.php
│   │   │   └── NewPasswordController.php
│   │   ├── UserController.php
│   │   └── RoleController.php
│   ├── Middleware/
│   │   ├── CheckPermission.php
│   │   └── EnsureUserIsActive.php
│   └── Requests/
│       ├── UserRequest.php
│       └── RoleRequest.php
├── Traits/
│   └── HasRoles.php
└── Providers/
    └── AppServiceProvider.php    (Blade directive)

database/
├── migrations/
│   ├── xxxx_add_is_active_to_users_table.php
│   ├── xxxx_create_roles_table.php
│   ├── xxxx_create_permissions_table.php
│   ├── xxxx_create_role_user_table.php
│   └── xxxx_create_permission_role_table.php
└── seeders/
    ├── DatabaseSeeder.php           (orchestrasi: Permission → Role → User)
    ├── PermissionSeeder.php
    ├── RoleSeeder.php
    └── UserSeeder.php

resources/views/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot-password.blade.php
│   └── reset-password.blade.php
├── layouts/
│   ├── app.blade.php
│   └── partials/
│       ├── sidebar.blade.php
│       └── navbar.blade.php
├── users/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
├── roles/
│   ├── index.blade.php
│   ├── create.blade.php
│   └── edit.blade.php
```

---

## 15. Urutan Implementasi

| Step | Task |
|---|---|
| 1 | Modifikasi migration users (tambah `is_active`) |
| 2 | Buat migration: roles, permissions, role_user, permission_role |
| 3 | Buat Model Role, Permission + relasi |
| 4 | Buat Trait `HasRoles` |
| 5 | Modifikasi Model User (tambah trait, `is_active` di fillable) |
| 6 | Buat Middleware `CheckPermission` + `EnsureUserIsActive` + register alias (`permission`, `active`) |
| 7 | Register Blade Directive `@hasPermission` |
| 8 | Buat Seeders: Permission, Role, User + orchestration di `DatabaseSeeder` |
| 9 | Konfigurasi `MAIL_*` di `.env` (untuk forgot password) |
| 10 | `php artisan migrate:fresh --seed` |
| 11 | Modifikasi Auth Controller dari Breeze (cek `is_active`, attach default role saat register, cache permission) |
| 12 | Buat Layout Bootstrap (`app.blade.php`) + partials + sidebar dinamis |
| 13 | Buat views `auth/` (login, register, forgot password, reset password) Bootstrap |
| 14 | Buat `UserController` + `UserRequest` + views `users/` |
| 15 | Buat `RoleController` + `RoleRequest` + views `roles/` |
| 16 | Setup routes di `web.php` |
| 17 | Testing end-to-end |

---

## 16. Catatan

- **Role default:** 4 role — `super-admin`, `admin`, `staff`, `manager`. Tidak ada role "Staff Gudang" (diganti `staff`).
- **Multi-role:** Satu user bisa punya banyak role. Permission user = gabungan semua permission dari semua role-nya.
- **Super Admin:** Selalu punya semua permission (termasuk permission baru yang ditambahkan kemudian). Tidak perlu di-attach di seeder.
- **Permission tanpa UI:** Permission tidak punya fitur CRUD di aplikasi. Semua perubahan permission dilakukan lewat `PermissionSeeder`.
- **Permission cache:** Disimpan di cache Laravel 1 jam. Panggil `clearPermissionCache()` setiap kali role/permission user berubah (update user, toggle role, update/delete role).
- **User non-aktif:** `EnsureUserIsActive` middleware memblokir user `is_active = false` di setiap request (bukan hanya saat login).
- **Hapus user:** Soft-deactivate (`is_active = false`), bukan hapus permanen.
- **Hapus role:** Diblokir jika ada user yang hanya memiliki role tersebut.
- **Controller Auth:** Mengikuti penamaan Breeze (`AuthenticatedSessionController`, `RegisteredUserController`, `PasswordResetLinkController`, `NewPasswordController`).
- **Default user:** `admin@example.com` / `password` → Super Admin.
