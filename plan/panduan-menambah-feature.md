# Panduan Menambah Fitur Baru (Lengkap)

> **Untuk:** Murid SMA yang akan mengerjakan proyek ini.
> **Tujuan:** Kamu bisa menambah fitur baru sendiri (misalnya fitur **Stok Masuk / Stock In**) tanpa harus sering bertanya ke pengajar.
>
> Ikuti langkah-langkah di bawah ini **secara berurutan**. Di setiap langkah ada penjelasan "kenapa" dan contoh kode yang bisa langsung kamu tiru.

---

## 0. Pahami Dulu Cara Kerja Aplikasi Ini

Aplikasi ini adalah aplikasi **Laravel 12** dengan pola **MVC (Model - View - Controller)**:

```
Browser  →  Route  →  Controller  →  Model  →  Database
                              ↓
                          View (Blade)  →  dikirim kembali ke Browser
```

Alur singkatnya:
1. User membuka halaman (URL).
2. **Route** menentukan Controller mana yang menangani URL itu.
3. **Controller** mengambil/mengolah data lewat **Model**.
4. **Controller** mengirim data ke **View (Blade)**.
5. **View** menampilkan hasilnya ke browser.

Jadi, untuk menambah satu fitur baru, kamu perlu menyentuh **6 bagian** berikut (akan dijelaskan detail nanti):

| No | Bagian | Fungsi |
|---|---|---|
| 1 | **Migration** | Membuat struktur tabel di database |
| 2 | **Model** | Menghubungkan tabel ke kode PHP |
| 3 | **Request** | Aturan validasi form |
| 4 | **Controller** | Logika simpan/ubah/hapus/cari data |
| 5 | **Route** | Mendaftarkan URL + proteksi permission |
| 6 | **View (Blade)** | Tampilan halaman (list & form) |

Selain itu ada **2 bagian pendukung**:
- **Seeder** → menambah data permission baru.
- **Sidebar** → menambah menu di samping kiri.

---

## 1. Standar/Konvensi yang Wajib Diikuti

Aplikasi ini punya aturan baku supaya semua fitur seragam. **Jangan menyimpang dari aturan ini.**

### 1.1 Setiap tabel WAJIB pakai kolom audit + soft delete

Setiap tabel di aplikasi ini **harus** punya kolom-kolom berikut:

| Kolom | Fungsi |
|---|---|
| `id` | primary key (otomatis) |
| `created_user_id` | siapa yang membuat data |
| `created_at` | waktu dibuat |
| `updated_user_id` | siapa yang terakhir mengubah |
| `updated_at` | waktu diubah terakhir |
| `deleted_user_id` | siapa yang menghapus |
| `deleted_at` | waktu dihapus (soft delete) |

Kamu **tidak perlu menulis kolom-kolom itu satu per satu**, karena sudah disediakan class `AuditableMigration`.

> **File:** `app/Database/Migrations/AuditableMigration.php`
> Class ini punya method `auditColumns($table)` yang otomatis menambahkan semua kolom di atas.

### 1.2 Setiap Model WAJIB pakai SoftDeletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class NamaModel extends Model
{
    use SoftDeletes; // WAJIB, supaya hapus = soft delete
}
```

### 1.3 Bahasa = Inggris

Semua teks di **UI** (tampilan) dan **flash message** harus **Bahasa Inggris**. Contoh: `"User added successfully."` bukan `"User berhasil ditambahkan."`

### 1.4 Form create & edit = 1 file

Jangan buat `create.blade.php` dan `edit.blade.php` terpisah. Gunakan **satu file** bernama `create-or-edit.blade.php`.

### 1.5 Kolom action di paling kiri

Di tabel list, kolom **Action** (tombol Edit/Hapus) ditaruh **paling kiri (kolom pertama)**, dan **tidak ada kolom nomor urut (#)**.

### 1.6 Tabel list wajib ada search

Setiap halaman list harus punya kotak pencarian di atas tabel.

---

## 2. Contoh Kasus: Menambah Fitur "Stock In" (Stok Masuk)

Sepanjang panduan ini kita akan pakai contoh membuat fitur **Stock In**.
Kamu tinggal ganti nama "stock-in" / "StockIn" dengan nama fiturmu sendiri.

Data yang akan disimpan (contoh):
- `item_name` (nama barang, text)
- `quantity` (jumlah, angka)
- `supplier` (pemasok, text)

---

## 3. Langkah 1 — Membuat Migration

Migration adalah "cetak biru" tabel di database.

### 3.1 Buat file migration lewat terminal

Jalankan perintah ini di terminal (folder root project):

```bash
php artisan make:migration create_stock_ins_table
```

> Perintah ini akan membuat file baru di `database/migrations/` dengan nama otomatis (mis. `2026_08_25_123456_create_stock_ins_table.php`).

### 3.2 Isi file migration

Buka file yang baru dibuat, lalu ubah isinya menjadi seperti ini:

```php
<?php

use App\Database\Migrations\AuditableMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends AuditableMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_ins', function (Blueprint $table) {
            // WAJIB: panggil ini dulu untuk kolom audit + soft delete
            $this->auditColumns($table);

            // Kolom khusus fitur kamu di bawah sini:
            $table->string('item_name');
            $table->unsignedInteger('quantity');
            $table->string('supplier')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_ins');
    }
};
```

**Penjelasan penting:**
- `extends AuditableMigration` (BUKAN `Migration`) → supaya bisa pakai `auditColumns`.
- `$this->auditColumns($table);` → otomatis menambah `id`, `created_at`, `updated_at`, `deleted_at`, `*_user_id`.
- Jangan menulis `$table->id()` atau `$table->timestamps()` lagi, karena sudah ditangani `auditColumns`.

**Referensi tipe kolom yang sering dipakai:**

| Kode | Hasil |
|---|---|
| `$table->string('nama');` | VARCHAR(255) |
| `$table->string('nama', 100);` | VARCHAR(100) |
| `$table->text('deskripsi');` | TEXT (panjang) |
| `$table->integer('jumlah');` | INT |
| `$table->unsignedInteger('jumlah');` | INT tanpa minus |
| `$table->decimal('harga', 10, 2);` | angka desimal |
| `$table->boolean('aktif');` | 0/1 |
| `$table->date('tanggal');` | tanggal |
| `$table->foreignId('user_id')->constrained();` | FK ke tabel lain |
| `$table->string('slug')->unique();` | unik |
| `->nullable()` | boleh kosong |
| `->default(0)` | nilai default |

### 3.3 Jalankan migration

```bash
php artisan migrate
```

---

## 4. Langkah 2 — Membuat Model

Model menghubungkan tabel ke kode PHP.

### 4.1 Buat model

```bash
php artisan make:model StockIn
```

> File baru: `app/Models/StockIn.php`

### 4.2 Isi model

Ubah file `app/Models/StockIn.php` menjadi:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockIn extends Model
{
    use SoftDeletes; // WAJIB

    protected $fillable = [
        'item_name',
        'quantity',
        'supplier',
    ];
}
```

**Penjelasan:**
- `use SoftDeletes;` → WAJIB, supaya hapus data menjadi soft delete (tidak hilang permanen).
- `$fillable` → daftar kolom yang **boleh diisi massal** (lewat `create()`/`update()`). Cantumkan semua kolom yang diisi dari form.

> **Catatan kolom audit:** `created_at`, `updated_at`, `deleted_at`, `created_user_id`, dll **tidak perlu** dimasukkan ke `$fillable`, karena diisi otomatis oleh sistem.

---

## 5. Langkah 3 — Membuat Request (Validasi)

Request berisi aturan validasi form (mis. nama wajib diisi, maksimal berapa huruf).

### 5.1 Buat request

```bash
php artisan make:request StockInRequest
```

> File baru: `app/Http/Requests/StockInRequest.php`

### 5.2 Isi request

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'supplier' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

**Aturan validasi umum:**

| Aturan | Arti |
|---|---|
| `required` | wajib diisi |
| `nullable` | boleh kosong |
| `string` | harus teks |
| `integer` | harus angka bulat |
| `email` | harus format email |
| `max:255` | maksimal 255 huruf |
| `min:1` | minimal 1 |
| `unique:stock_ins,item_name` | tidak boleh sama |

---

## 6. Langkah 4 — Membuat Controller

Controller berisi logika utama: tampilkan list, simpan, ubah, hapus.

### 6.1 Buat controller

```bash
php artisan make:controller StockInController
```

> File baru: `app/Http/Controllers/StockInController.php`

### 6.2 Isi controller

Salin & sesuaikan kode berikut:

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Models\StockIn;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockInController extends Controller
{
    /**
     * List dengan search + pagination.
     */
    public function index(Request $request): View
    {
        $query = StockIn::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('supplier', 'like', "%{$search}%");
            });
        }

        $stockIns = $query->latest()->paginate(10)->withQueryString();

        return view('stock-ins.index', compact('stockIns'));
    }

    /**
     * Tampilkan form tambah.
     */
    public function create(): View
    {
        return view('stock-ins.create-or-edit');
    }

    /**
     * Simpan data baru.
     */
    public function store(StockInRequest $request): RedirectResponse
    {
        StockIn::create($request->validated());

        return redirect()->route('stock-ins.index')->with('success', 'Stock In added successfully.');
    }

    /**
     * Tampilkan form edit.
     */
    public function edit(StockIn $stockIn): View
    {
        return view('stock-ins.create-or-edit', compact('stockIn'));
    }

    /**
     * Update data.
     */
    public function update(StockInRequest $request, StockIn $stockIn): RedirectResponse
    {
        $stockIn->update($request->validated());

        return redirect()->route('stock-ins.index')->with('success', 'Stock In updated successfully.');
    }

    /**
     * Hapus (soft delete).
     */
    public function destroy(StockIn $stockIn): RedirectResponse
    {
        $stockIn->delete();

        return redirect()->route('stock-ins.index')->with('success', 'Stock In deleted successfully.');
    }
}
```

**Penjelasan:**
- `index()` → menampilkan list dengan **search** dan **pagination** (10 data per halaman).
- `create()` → menampilkan form kosong.
- `store()` → menyimpan data baru (pakai `StockInRequest` untuk validasi).
- `edit()` → menampilkan form berisi data lama.
- `update()` → memperbarui data.
- `destroy()` → menghapus (otomatis soft delete karena Model pakai `SoftDeletes`).
- `$request->validated()` → mengambil data yang sudah lolos validasi.

---

## 7. Langkah 5 — Mendaftarkan Route

Route menghubungkan URL ke Controller, dan memberi proteksi permission.

Buka `routes/web.php`, lalu tambahkan blok berikut **di dalam** group `['auth', 'active']` (di bawah komentar `// Roles` misalnya):

```php
    // Stock Ins
    Route::get('stock-ins', [StockInController::class, 'index'])->middleware('permission:stock-in.read')->name('stock-ins.index');
    Route::get('stock-ins/create', [StockInController::class, 'create'])->middleware('permission:stock-in.create')->name('stock-ins.create');
    Route::post('stock-ins', [StockInController::class, 'store'])->middleware('permission:stock-in.create')->name('stock-ins.store');
    Route::get('stock-ins/{stockIn}/edit', [StockInController::class, 'edit'])->middleware('permission:stock-in.update')->name('stock-ins.edit');
    Route::put('stock-ins/{stockIn}', [StockInController::class, 'update'])->middleware('permission:stock-in.update')->name('stock-ins.update');
    Route::delete('stock-ins/{stockIn}', [StockInController::class, 'destroy'])->middleware('permission:stock-in.delete')->name('stock-ins.destroy');
```

**Jangan lupa** menambahkan `use` di bagian atas file `web.php`:

```php
use App\Http\Controllers\StockInController;
```

**Penjelasan:**
- `middleware('permission:stock-in.read')` → hanya user yang punya permission `stock-in.read` yang bisa akses.
- `{stockIn}` → parameter model (Laravel otomatis mencari data berdasarkan id).
- Nama route (`->name(...)`) dipakai di Blade & controller (mis. `route('stock-ins.index')`).

---

## 8. Langkah 6 — Membuat View (Blade)

View adalah tampilan halaman. Kamu perlu **2 file**:

| File | Fungsi |
|---|---|
| `resources/views/stock-ins/index.blade.php` | halaman list |
| `resources/views/stock-ins/create-or-edit.blade.php` | form tambah & edit (digabung) |

### 8.1 View List — `index.blade.php`

Buat file `resources/views/stock-ins/index.blade.php`:

```blade
@extends('layouts.app')

@section('title', 'Stock In')
@section('page-title', 'Stock In')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Stock In List</h4>
        @hasPermission('stock-in.create')
            <a href="{{ route('stock-ins.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Add Stock In
            </a>
        @endhasPermission
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="row g-2 mb-3">
                <div class="col-md-9">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Search item or supplier...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockIns as $item)
                            <tr>
                                <td class="text-nowrap">
                                    @hasPermission('stock-in.update')
                                        <a href="{{ route('stock-ins.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endhasPermission
                                    @hasPermission('stock-in.delete')
                                        <form action="{{ route('stock-ins.destroy', $item) }}" method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endhasPermission
                                </td>
                                <td>{{ $item->item_name }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->supplier }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No data found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $stockIns->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection
```

### 8.2 View Form — `create-or-edit.blade.php`

Buat file `resources/views/stock-ins/create-or-edit.blade.php`:

```blade
@extends('layouts.app')

@php
    $isEdit = isset($stockIn) && $stockIn->exists;
@endphp

@section('title', $isEdit ? 'Edit Stock In' : 'Add Stock In')
@section('page-title', $isEdit ? 'Edit Stock In' : 'Add Stock In')

@section('content')
    <div class="card">
        <div class="card-header">{{ $isEdit ? 'Edit Stock In Form' : 'Add Stock In Form' }}</div>
        <div class="card-body">
            <form method="POST" action="{{ $isEdit ? route('stock-ins.update', $stockIn) : route('stock-ins.store') }}">
                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="item_name" class="form-label">Item Name</label>
                    <input type="text" name="item_name" id="item_name"
                           value="{{ old('item_name', $stockIn->item_name ?? '') }}"
                           class="form-control @error('item_name') is-invalid @enderror" required>
                    @error('item_name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantity</label>
                    <input type="number" name="quantity" id="quantity"
                           value="{{ old('quantity', $stockIn->quantity ?? '') }}"
                           class="form-control @error('quantity') is-invalid @enderror" required>
                    @error('quantity')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="supplier" class="form-label">Supplier</label>
                    <input type="text" name="supplier" id="supplier"
                           value="{{ old('supplier', $stockIn->supplier ?? '') }}"
                           class="form-control @error('supplier') is-invalid @enderror">
                    @error('supplier')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('stock-ins.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
```

**Penjelasan penting soal form gabungan:**
- `$isEdit = isset($stockIn) && $stockIn->exists;` → menentukan mode.
- `action` form berubah: `update` saat edit, `store` saat create.
- `@method('PUT')` hanya muncul saat edit.
- `old('field', $stockIn->field ?? '')` → menampilkan nilai lama saat edit, kosong saat create.

---

## 9. Langkah 7 — Menambah Permission (Seeder)

Supaya menu & route bisa diakses, tambahkan permission di seeder.

Buka `database/seeders/PermissionSeeder.php`, lalu tambahkan ke array `$permissions`:

```php
['name' => 'View Stock In', 'slug' => 'stock-in.read', 'module' => 'stock-in'],
['name' => 'Create Stock In', 'slug' => 'stock-in.create', 'module' => 'stock-in'],
['name' => 'Update Stock In', 'slug' => 'stock-in.update', 'module' => 'stock-in'],
['name' => 'Delete Stock In', 'slug' => 'stock-in.delete', 'module' => 'stock-in'],
```

**Pola slug permission:** `<module>.<aksi>` → `stock-in.read`, `stock-in.create`, dst.

> **Catatan:** Jika kamu menambah permission baru, jalankan `php artisan db:seed` agar masuk ke database. Tapi hati-hati, `db:seed` juga me-reset role. Koordinasikan dengan pengajar jika ragu.

---

## 10. Langkah 8 — Menambah Menu di Sidebar

Buka `resources/views/layouts/partials/sidebar.blade.php`, lalu tambahkan di dalam `<ul>`:

```blade
@hasPermission('stock-in.read')
<li class="nav-item">
    <a href="{{ route('stock-ins.index') }}" class="nav-link {{ request()->routeIs('stock-ins.*') ? 'active' : '' }}">
        <i class="bi bi-box-arrow-in-down"></i> Stock In
    </a>
</li>
@endhasPermission
```

**Icon Bootstrap** bisa kamu cari di https://icons.getbootstrap.com (contoh: `bi bi-box`, `bi bi-truck`, `bi bi-archive`).

---

## 11. Jalankan & Uji

Setelah semua langkah selesai, jalankan:

```bash
php artisan migrate
php artisan db:seed
php artisan serve
```

Lalu buka `http://localhost:8000/stock-ins` dan uji:
1. Tambah data (Add).
2. Cari data (search).
3. Edit data.
4. Hapus data.

---

## 12. Checklist Lengkap (Jangan Sampai Ada yang Terlewat)

- [ ] Migration dibuat & `extends AuditableMigration` + panggil `auditColumns()`
- [ ] `php artisan migrate` berhasil
- [ ] Model dibuat + `use SoftDeletes` + `$fillable` lengkap
- [ ] Request dibuat + aturan validasi lengkap
- [ ] Controller dibuat (index, create, store, edit, update, destroy)
- [ ] Route didaftarkan + `use` controller di `web.php`
- [ ] View `index.blade.php` dibuat (search + Action di kiri)
- [ ] View `create-or-edit.blade.php` dibuat (form gabungan)
- [ ] Permission ditambahkan di `PermissionSeeder`
- [ ] Menu ditambahkan di `sidebar.blade.php`
- [ ] Semua teks UI Bahasa Inggris
- [ ] Diuji: tambah, cari, edit, hapus

---

## 13. Pertanyaan Sering Muncul (FAQ)

**Q: Kenapa tidak perlu menulis `$table->id()` di migration?**
A: Karena `auditColumns()` sudah otomatis menambahkan `id`.

**Q: Kenapa hapus data tidak hilang dari database?**
A: Karena aplikasi pakai **soft delete**. Data diberi tanda `deleted_at`, jadi masih ada di database tapi tidak muncul di list. Ini memang disengaja.

**Q: Bagaimana kalau fitur punya relasi (mis. barang punya kategori)?**
A: Gunakan `foreignId` di migration + method relasi (`belongsTo`/`hasMany`/`belongsToMany`) di Model. Minta contoh ke pengajar untuk kasus relasi yang lebih rumit.

**Q: Apa bedanya `store` dan `update`?**
A: `store` = menyimpan data BARU (method POST). `update` = mengubah data yang SUDAH ADA (method PUT/PATCH).

**Q: `old()` itu apa?**
A: `old('field')` menampilkan kembali nilai yang sudah diketik user ketika validasi gagal, supaya user tidak mengetik ulang.

**Q: Kenapa ada `@hasPermission(...)`?**
A: Itu directive khusus aplikasi ini untuk menyembunyikan tombol/menu dari user yang tidak punya permission.
