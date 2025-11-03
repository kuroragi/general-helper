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

### 2. Blameable Macro

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

### 3. Activity Log Service

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

### 4. Activity Log Reader

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

### 5. General Helper Class

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
php artisan vendor:publish --tag="kuroragi-general-helper-config"
```

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

Gunakan Laravel Scheduler untuk merotasi log mingguan:
Tambahkan pada `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->call(function () {
        \Kuroragi\GeneralHelper\Services\ActivityLogger::rotateWeekly();
    })->weeklyOn(1, '01:00');
}
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
│ └─ Macros/EloquentMacros.php
├─ config/kuroragi.php
├─ resources/
├─ README.md
└─ tests/
