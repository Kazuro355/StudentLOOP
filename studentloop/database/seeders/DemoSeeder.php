<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WantedRequest;
use Illuminate\Database\Seeder;

/**
 * Data contoh untuk mencoba aplikasi. Jangan dijalankan di produksi.
 * Semua akun demo memakai kata sandi: password123
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $cat = fn (string $slug) => Category::where('slug', $slug)->value('id');

        $people = [
            ['Andika Busthomi', 'andika@demo.test', '2602000001', 'Cyber Security', '2023', 'Kemanggisan'],
            ['Marcel', 'marcel@demo.test', '2602000002', 'Information Systems', '2022', 'Kemanggisan'],
            ['Dafi', 'dafi@demo.test', '2602000003', 'Computer Science', '2023', 'Alam Sutera'],
            ['Daffa', 'daffa@demo.test', '2602000004', 'Desain Komunikasi Visual', '2022', 'Syahdan'],
            ['Eric', 'eric@demo.test', '2602000005', 'Software Engineering', '2023', 'Kemanggisan'],
        ];

        $users = [];
        foreach ($people as [$name, $email, $nim, $jurusan, $angkatan, $kampus]) {
            $users[$email] = User::updateOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password123',
                'nim' => $nim,
                'jurusan' => $jurusan,
                'angkatan' => $angkatan,
                'kampus' => $kampus,
                'profile_completed_at' => now(),
            ]);
        }

        [$andika, $marcel, $dafi, $daffa, $eric] = array_values($users);

        $items = [
            [$marcel, 'sewa', 'Kalkulator Scientific Casio fx-991', 'elektronik', 'Baik', 'Casing dan baterai cadangan', 8000, 20000, 'Kemanggisan', 'Lobi gedung Anggrek', 'Dipakai untuk kuliah Kalkulus dan Statistik, semua fungsi normal.'],
            [$marcel, 'beli', 'Buku Kalkulus Edisi 9', 'buku', 'Seperti baru', null, 120000, 0, 'Kemanggisan', 'Perpustakaan lantai 1', 'Tanpa coretan, ada catatan kecil di bab 3.'],
            [$dafi, 'sewa', 'Tripod Kamera Aluminium', 'fotografi', 'Baik', 'Tas tripod', 15000, 50000, 'Alam Sutera', 'Gerbang utama', 'Tinggi maksimal 160 cm, kuat untuk kamera mirrorless.'],
            [$dafi, 'beli', 'Charger Laptop 65W USB-C', 'elektronik', 'Baik', 'Kabel asli', 150000, 0, 'Alam Sutera', null, 'Original, dipakai 6 bulan, tidak ada kerusakan.'],
            [$daffa, 'sewa', 'Kamera Mirrorless Sony A6000', 'fotografi', 'Baik', 'Lensa kit, 2 baterai, tas', 90000, 300000, 'Syahdan', 'Kantin Syahdan', 'Cocok untuk tugas video dan fotografi. Wajib jaga kebersihan lensa.'],
            [$daffa, 'beli', 'Almamater BINUS (L)', 'alat-lab', 'Seperti baru', null, 45000, 0, 'Syahdan', null, 'Almamater BINUS ukuran L, kondisi seperti baru dan sudah dicuci.'],
            [$andika, 'beli', 'Buku Basis Data', 'buku', 'Baik', null, 80000, 0, 'Kemanggisan', 'Kantin Kemanggisan', 'Edisi terbaru, ada stabilo di beberapa halaman.'],
            [$andika, 'sewa', 'Papan Gambar A3', 'perlengkapan', 'Baik', 'Penggaris T', 10000, 30000, 'Kemanggisan', null, 'Untuk tugas desain teknik.'],
        ];

        foreach ($items as [$owner, $type, $title, $slug, $cond, $kelengkapan, $price, $deposit, $kampus, $meet, $desc]) {
            $item = Item::updateOrCreate(['user_id' => $owner->id, 'title' => $title], [
                'category_id' => $cat($slug),
                'type' => $type,
                'description' => $desc,
                'condition' => $cond,
                'kelengkapan' => $kelengkapan,
                'price' => $price,
                'deposit' => $deposit,
                'min_days' => 1,
                'max_days' => 14,
                'pickup_method' => 'ambil_kampus',
                'meeting_point' => $meet,
                'kampus' => $kampus,
                'status' => 'tersedia',
                'views' => random_int(0, 80),
            ]);

            if (! $item->photos()->exists()) {
                $directory = storage_path('app/public/items');
                if (! is_dir($directory)) {
                    mkdir($directory, 0755, true);
                }

                if (str_contains($title, 'Almamater BINUS')) {
                    $filename = 'binus-almamater.svg';
                } else {
                    $filename = 'demo-' . substr(hash('sha256', $title), 0, 12) . '.svg';
                    $safeTitle = e($title);
                    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">'
                        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#eee9ff"/><stop offset="1" stop-color="#d8d0ff"/></linearGradient></defs>'
                        . '<rect width="800" height="600" rx="32" fill="url(#g)"/>'
                        . '<circle cx="400" cy="220" r="92" fill="#5b3de0" opacity=".13"/>'
                        . '<path d="M355 190h90v76h-90z" fill="#5b3de0" opacity=".28"/>'
                        . '<text x="400" y="390" text-anchor="middle" font-family="Arial,sans-serif" font-size="38" font-weight="700" fill="#33266f">' . $safeTitle . '</text>'
                        . '<text x="400" y="440" text-anchor="middle" font-family="Arial,sans-serif" font-size="22" fill="#665d88">Foto demo StudentLOOP</text>'
                        . '</svg>';
                    file_put_contents($directory . '/' . $filename, $svg);
                }
                $item->photos()->create([
                    'path' => 'items/' . $filename,
                    'position' => 1,
                ]);
            }
        }

        WantedRequest::updateOrCreate(['user_id' => $dafi->id, 'title' => 'Jas lab ukuran M untuk praktikum'], [
            'category_id' => $cat('alat-lab'),
            'details' => 'Dibutuhkan untuk praktikum minggu depan, cukup dipinjam beberapa hari.',
            'mode' => 'sewa',
            'budget_max' => 20000,
            'kampus' => 'Alam Sutera',
            'status' => 'aktif',
        ]);

        UserNotification::firstOrCreate(
            ['user_id' => $andika->id, 'title' => 'Selamat datang di StudentLOOP'],
            ['type' => 'keamanan', 'body' => 'Lengkapi profil, jelajahi barang, atau tawarkan barangmu ke sesama Binusian.', 'url' => route('beranda')]
        );
    }
}
