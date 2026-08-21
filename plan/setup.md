# Plan: Project Setup — Inventory App

> **Stack:** Laravel 12+ + Bootstrap 5 + MySQL  
> **Tujuan:** Setup project dari awal hingga siap untuk development fitur.

---

## 1. Install Laravel

```bash
composer create-project laravel/laravel inventory-app
cd inventory-app
```

---

## 2. Konfigurasi Database MySQL

### 2.1 Buat database di MySQL
```sql
CREATE DATABASE inventory_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2.2 Update file `.env`
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=inventory_db
DB_USERNAME=root
DB_PASSWORD=
```

---

## 3. Install Bootstrap 5

### Opsi A — Via npm (disarankan, bisa custom SCSS)
```bash
npm install bootstrap @popperjs/core
npm install -D sass
```

Buat/update file `resources/sass/app.scss`:
```scss
@import ''~bootstrap/scss/bootstrap'';
```

Update `resources/js/app.js`:
```js
import ''./bootstrap'';
import ''bootstrap'';
```

Update `vite.config.js`:
```js
import { defineConfig } from ''vite'';
import laravel from ''laravel-vite-plugin'';

export default defineConfig({
    plugins: [
        laravel({
            input: [''resources/sass/app.scss'', ''resources/js/app.js''],
            refresh: true,
        }),
    ],
});
```

### Opsi B — Via CDN (lebih cepat, tanpa build tool)
Tambahkan di `<head>` layout:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
```

---

## 4. Install Bootstrap Icons (Opsional — untuk icon di sidebar & tombol)

```bash
npm install bootstrap-icons
```

Import di `resources/sass/app.scss`:
```scss
@import ''~bootstrap-icons/font/bootstrap-icons.css'';
```

Atau via CDN:
```html
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
```

---

## 5. Install Laravel Breeze (Auth Scaffolding)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

> **Catatan:** Breeze default menggunakan Tailwind. Nanti layout Tailwind akan diganti dengan Bootstrap 5 secara manual. Untuk alternatif, bisa juga buat auth manual tanpa Breeze.

Setelah Breeze terinstall, hapus dependency Tailwind yang tidak terpakai:
```bash
npm remove tailwindcss postcss autoprefixer
```
Hapus file `tailwind.config.js` dan `postcss.config.js`.

---

## 6. Jalankan Development Server

```bash
npm run dev
```

Lalu buka terminal baru:
```bash
php artisan serve
```

Buka browser: `http://localhost:8000`


## 7. Struktur Folder Awal Project

```
inventory-app/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
│   ├── js/
│   ├── sass/
│   └── views/
├── routes/
│   └── web.php
├── storage/
├── tests/
├── .env
├── composer.json
├── package.json
└── vite.config.js
```

---

## 8. Urutan Setup (Checklist)

| Step | Task | Cek |
|---|---|---|
| 1 | `composer create-project laravel/laravel inventory-app` | ☐ |
| 2 | Buat database `inventory_db` di MySQL | ☐ |
| 3 | Update `.env` (DB config) | ☐ |
| 4 | `npm install bootstrap @popperjs/core bootstrap-icons sass -D` | ☐ |
| 5 | Konfigurasi `vite.config.js`, `app.scss`, `app.js` | ☐ |
| 6 | `composer require laravel/breeze --dev` | ☐ |
| 7 | `php artisan breeze:install blade` | ☐ |
| 8 | Hapus Tailwind + bersihkan file config | ☐ |
| 9 | `npm run dev` & `php artisan serve` → test | ☐ |

---

## 9. Catatan

- **Breeze vs Custom Auth:** Breeze digunakan untuk mempercepat scaffolding auth (login, register, forgot password). Layout Tailwind-nya akan diganti Bootstrap 5 saat implementasi layout.
- **Jika tidak ingin Breeze:** Bisa buat auth manual dengan controller sendiri, tapi akan memakan waktu lebih lama.
- **Opsi CDN vs npm:** Jika tidak ingin build tool (Vite), pakai CDN. Tapi npm lebih fleksibel untuk kustomisasi SCSS.
- **app.scss:** File ini tidak otomatis dibuat. Bisa dibuat manual di `resources/sass/app.scss`.
