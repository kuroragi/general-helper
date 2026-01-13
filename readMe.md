# Kuroragi General Helper

**Kuroragi General Helper** adalah paket Laravel serbaguna yang menyediakan kumpulan fitur umum untuk mempercepat pengembangan aplikasi Laravel.
Paket ini berfokus pada integrasi _blameable traits_, _activity logging_, dan _helper_ umum yang sering digunakan pada proyek-proyek Laravel.

Paket ini kompatibel dengan **Laravel ^10** dan memiliki ketergantungan terhadap:

- [`spatie/laravel-permission`](https://github.com/spatie/laravel-permission)
- [`barryvdh/laravel-dompdf`](https://github.com/barryvdh/laravel-dompdf)

---

## ✨ Fitur Utama

### 1. Blameable Trait

`Blameable` digunakan untuk secara otomatis menambahkan kolom dan relasi terhadap model pengguna (`User`) atau model autentikasi lain yang ditentukan.

**Fungsi:**

- Otomatis mengisi kolom `created_by`, `updated_by`, dan `deleted_by`.
- Memberikan relasi ke model pengguna melalui `createdBy()`, `updatedBy()`, dan `deletedBy()`.

**Cara pakai:**

```php
use Kuroragi\GeneralHelper\Traits\Blameable;

class Post extends Model
{
    use Blameable;
}
```

> Pastikan tabel Anda memiliki kolom `created_by`, `updated_by`, dan `deleted_by`.

---

### 2. Blameable Migration Macro

Menambahkan _macro_ Blueprint untuk migration, sehingga kolom blameable dapat ditambahkan dengan mudah:

```php
// Di dalam migration file
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('content');
    $table->timestamps();
    $table->softDeletes();
    
    // Menambahkan kolom created_by, updated_by, deleted_by sekaligus
    $table->blameable();
});
```

**Variasi penggunaan:**

```php
// Dengan foreign key ke tabel users (default)
$table->blameable();

// Dengan foreign key ke tabel custom
$table->blameable('admins');

// Tanpa foreign key constraint
$table->blameable(null, false);

// Hanya kolom tertentu
$table->createdBy();
$table->updatedBy();
$table->deletedBy();

// Drop kolom blameable (untuk rollback)
$table->dropBlameable('posts'); // parameter: nama tabel saat ini
```

---

### 3. Blameable Query Macro

Menambahkan _macro_ Eloquent Query Builder:

```php
Model::createdBy($userId)
Model::updatedBy($userId)
Model::deletedBy($userId)
```

Contoh:

```php
Post::createdBy(auth()->id())->get();
```

---

### 4. Activity Log Service

Service ini bertugas mencatat setiap transaksi penting ke dalam file log di:

```
storage/logs/activity/
```

**Struktur file:**

```
activity-2025-11-02.log
```

**Fitur:**

- Setiap aksi disimpan ke dalam file log harian.
- File log akan **di-rotate setiap minggu (Senin 01:00)** untuk menghemat penyimpanan.
- File lama akan dikompres atau dihapus sesuai konfigurasi.

**Contoh penggunaan:**

```php
use Kuroragi\GeneralHelper\Services\ActivityLogger;

ActivityLogger::info('User membuat data baru', [
    'user_id' => auth()->id(),
    'model' => 'Post',
    'action' => 'create'
]);
```

---

### 5. Activity Log Reader

Menyediakan service untuk membaca isi log aktivitas.

**Fitur:**

- Secara default hanya membaca 50 baris terakhir.
- Mendukung pencarian berdasarkan **keyword** atau **kategori**.
- Mendukung pembacaan seluruh log (termasuk log yang sudah di-roll).
- Mendukung pembatasan waktu dengan **range datetime**.

**Contoh penggunaan:**

```php
use Kuroragi\GeneralHelper\Services\ActivityLogReader;

// membaca 50 data terakhir
$logs = ActivityLogReader::read();

// mencari dengan keyword
$logs = ActivityLogReader::search('deleted post');

// membaca log dalam rentang waktu
$logs = ActivityLogReader::range('2025-11-01', '2025-11-02');
```

---

### 6. Authorization Exception Handler

Paket ini secara otomatis menangani **AuthorizationException (403)** ketika user mencoba mengakses halaman yang tidak diizinkan untuk role mereka.

**Fitur:**

- Redirect otomatis ke halaman dashboard (atau route lain yang dikonfigurasi)
- Menampilkan pesan error melalui session flash message
- Mendukung JSON response untuk AJAX/Livewire requests
- Dapat dikonfigurasi sesuai kebutuhan

**Cara kerja:**

Ketika user mengakses route yang dilindungi oleh middleware permission/role dan mereka tidak memiliki akses:

```php
// Contoh route dengan middleware
Route::get('/admin/users', [UserController::class, 'index'])
    ->middleware('permission:manage users');
```

Jika user tanpa permission `manage users` mengakses route tersebut, mereka akan:
- Diarahkan ke halaman dashboard
- Mendapatkan pesan: "Kamu tidak memiliki hak akses ke halaman tersebut."

**Menampilkan pesan di view:**

```blade
@if(session('no_access'))
    <div class="alert alert-danger">
        {{ session('no_access') }}
    </div>
@endif
```

**Konfigurasi:**

Anda dapat mengubah perilaku di `config/kuroragi.php`:

```php
'authorization_exception' => [
    'enabled' => true, // enable/disable handler
    'redirect_type' => 'route', // 'route', 'url', 'back', 'home'
    'redirect_to' => 'dashboard', // route name atau URL (tergantung redirect_type)
    'session_key' => 'no_access', // key session flash message
    'message' => 'Kamu tidak memiliki hak akses ke halaman tersebut.',
    'json_response' => true, // handle AJAX/Livewire requests
],
```

**Opsi Redirect Type:**

- `'route'`: Redirect ke route name tertentu
  ```php
  'redirect_type' => 'route',
  'redirect_to' => 'dashboard', // nama route
  ```

- `'url'`: Redirect ke URL tertentu
  ```php
  'redirect_type' => 'url',
  'redirect_to' => '/admin/forbidden', // URL path
  ```

- `'back'`: Redirect ke halaman sebelumnya
  ```php
  'redirect_type' => 'back',
  'redirect_to' => null, // diabaikan
  ```

- `'home'`: Redirect ke home page (/)
  ```php
  'redirect_type' => 'home',
  'redirect_to' => null, // diabaikan
  ```

---

### 7. General Helper Class

Berisi kumpulan fungsi statis umum yang sering digunakan pada proyek Laravel.

**Contoh isi `GeneralHelper`:**

| Method                          | Fungsi                                                    |
| ------------------------------- | --------------------------------------------------------- |
| `getSlug($string)`              | Membuat slug dari teks                                    |
| `convertDateToIndo($date)`      | Mengubah format tanggal ke format Indonesia lengkap       |
| `convertDateToIndoShort($date)` | Mengubah format tanggal ke format singkat Indonesia       |
| `getTerbilang($number)`         | Mengubah angka menjadi kalimat terbilang Bahasa Indonesia |
| `getIndoDate($date)`            | Menghasilkan tanggal dalam format Indonesia               |
| `getIndoDateTerbilang($date)`   | Menghasilkan tanggal lengkap dengan terbilang             |

**Contoh penggunaan:**

```php
use Kuroragi\GeneralHelper\Helpers\GeneralHelper;

echo GeneralHelper::getSlug('Halo Dunia!'); // "halo-dunia"
echo GeneralHelper::convertDateToIndo('2025-11-02'); // "2 November 2025"
```

---

## ⚙️ Instalasi

### Melalui Composer (Packagist)

```bash
composer require kuroragi/general-helper
```

### Instalasi Lokal (development)

Clone repository ke dalam folder proyek Laravel:

```bash
git clone https://github.com/kuroragi/general-helper.git packages/kuroragi/general-helper
```

Tambahkan di `composer.json`:

```json
"repositories": [
    {
        "type": "path",
        "url": "packages/kuroragi/general-helper"
    }
]
```

Lalu jalankan:

```bash
composer require kuroragi/general-helper:*@dev
```

---

## 🔧 Konfigurasi

Publikasikan konfigurasi (jika tersedia):

```bash
php artisan vendor:publish --tag=config --provider="Kuroragi\GeneralHelper\Providers\GeneralHelperServiceProvider"
```

File konfigurasi akan dipublikasikan ke `config/kuroragi.php`. Berikut opsi yang tersedia:

```php
return [
    'activity_log_path' => storage_path('logs/activity'),
    'activity_log_file_prefix' => 'activity-',
    'roll_day' => 'monday',
    'roll_time' => '01:00', // HH:MM format
    'default_reader_limit' => 50,
    'auth_model' => null, // null => use config('auth.providers.users.model')
];
```

**Penjelasan konfigurasi:**

- `activity_log_path`: Path tempat menyimpan file log aktivitas
- `activity_log_file_prefix`: Prefix nama file log
- `roll_day`: Hari untuk merotasi log mingguan (sunday, monday, tuesday, dst.)
- `roll_time`: Waktu untuk merotasi log (format HH:MM)
- `default_reader_limit`: Jumlah baris default yang dibaca oleh ActivityLogReader
- `auth_model`: Model untuk autentikasi (null akan menggunakan default dari config auth)
- `authorization_exception`: Konfigurasi untuk menangani exception 403
  - `enabled`: Aktifkan/nonaktifkan handler
  - `redirect_type`: Tipe redirect ('route', 'url', 'back', 'home')
  - `redirect_to`: Tujuan redirect (route name atau URL, tergantung redirect_type)
  - `session_key`: Key untuk session flash message
  - `message`: Pesan error yang ditampilkan
  - `json_response`: Handle request JSON/AJAX

---

## 🧩 Dependensi

Pastikan kamu sudah menginstal dependensi berikut:

```bash
composer require spatie/laravel-permission
composer require barryvdh/laravel-dompdf
```

---

## 🧪 Pengujian

Menjalankan test:

```bash
php artisan test --filter=Kuroragi
```

---

## 📦 Jadwal Roll Log

Paket ini **secara otomatis** mendaftarkan jadwal rotasi log mingguan melalui Service Provider.
Scheduler akan berjalan sesuai dengan konfigurasi `roll_day` dan `roll_time` yang diatur di `config/kuroragi.php`.

**Default:** Setiap hari Senin pukul 01:00

Anda juga dapat menjalankan rotasi log secara manual dengan command:

```bash
php artisan kuroragi:roll-activity-logs
```

**Catatan:** Pastikan Laravel Scheduler berjalan dengan menambahkan cron entry:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📚 Lisensi

Paket ini dirilis di bawah lisensi **MIT**.

---

## 🧠 Catatan untuk Copilot & Kontributor

Proyek ini dibuat sebagai bahan pembelajaran dan dasar pengembangan untuk paket Laravel modular yang menggabungkan trait, macro, helper, dan service logging.
Strukturnya dirancang agar:

- Mudah dikembangkan ke fitur tambahan (misalnya audit trail, notifikasi, dan API log).
- Dapat diinstal baik lokal maupun melalui Packagist.
- Memiliki _namespace_ dan struktur yang rapi untuk dioptimalkan dengan **GitHub Copilot** atau AI asisten pengembang lainnya.

## Struktur Aplikasi

kuroragi-general-helper/
├─ composer.json
├─ src/
│ ├─ GeneralHelper.php
│ ├─ Traits/Blameable.php
│ ├─ ActivityLog/
│ │ ├─ ActivityLogger.php
│ │ ├─ ActivityLogReader.php
│ │ └─ Commands/RollActivityLogs.php
│ ├─ Providers/GeneralHelperServiceProvider.php
│ └─ Macros/
│   ├─ EloquentMacros.php
│   └─ BlueprintMacros.php
├─ config/kuroragi.php
├─ resources/
├─ README.md
└─ tests/
