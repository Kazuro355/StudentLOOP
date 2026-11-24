<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\WantedRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentLoopFlowTest extends TestCase
{
    use RefreshDatabase;

    private function member(array $attrs = []): User
    {
        return User::factory()->create($attrs + [
            'nim' => (string) random_int(2600000000, 2699999999),
            'jurusan' => 'Cyber Security',
            'angkatan' => '2023',
            'kampus' => 'Kemanggisan',
            'profile_completed_at' => now(),
        ]);
    }

    private function item(User $owner, array $attrs = []): Item
    {
        $cat = Category::firstOrCreate(['slug' => 'elektronik'], ['name' => 'Elektronik']);

        return Item::create($attrs + [
            'user_id' => $owner->id, 'category_id' => $cat->id, 'type' => 'sewa', 'title' => 'Kalkulator',
            'description' => 'Baik', 'condition' => 'Baik', 'price' => 10000, 'deposit' => 20000,
            'min_days' => 1, 'max_days' => 14, 'pickup_method' => 'ambil_kampus', 'kampus' => 'Kemanggisan', 'status' => 'tersedia',
        ]);
    }

    public function test_register_with_any_email_needs_no_binus_verification(): void
    {
        $this->post('/daftar', [
            'name' => 'Andika', 'nim' => '2602123456', 'email' => 'andika@gmail.com',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect('/lengkapi-profil');

        $this->assertAuthenticated();

        // Belum lengkap profil -> diarahkan ke Lengkapi profil, bukan verifikasi email.
        $this->get('/beranda')->assertRedirect('/lengkapi-profil');

        $this->post('/lengkapi-profil', [
            'name' => 'Andika', 'nim' => '2602123456', 'jurusan' => 'Cyber Security',
            'angkatan' => '2023', 'kampus' => 'Kemanggisan',
        ])->assertRedirect('/beranda');

        $this->get('/beranda')->assertOk();
    }

    public function test_main_pages_render_for_a_member(): void
    {
        $me = $this->member();
        $item = $this->item($this->member());

        foreach (['/beranda', '/cari', '/favorit', '/transaksi', '/pinjaman-saya', '/pesan', '/notifikasi', '/dicari', '/dicari/buat',
                     '/profil', '/profil/ubah', '/saldo', '/barang-saya', '/jual-barang', '/sewakan-barang', '/laporkan', '/panduan-pencarian',
                     '/terpopuler', '/baru-ditambahkan', '/di-dekatmu', '/rekomendasi', "/barang/{$item->id}", "/barang/{$item->id}/sewa"] as $url) {
            $this->actingAs($me)->get($url)->assertOk();
        }
    }

    public function test_rental_flow_from_request_to_review(): void
    {
        Storage::fake('public');
        $owner = $this->member();
        $renter = $this->member();
        $item = $this->item($owner);

        $start = now()->addDays(2)->toDateString();
        $end = now()->addDays(4)->toDateString();

        $this->actingAs($renter)->post("/barang/{$item->id}/pesan", [
            'pickup_method' => 'ambil_kampus', 'start_date' => $start, 'end_date' => $end,
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('menunggu_persetujuan', $order->status);
        $this->assertSame(3, $order->days);
        $this->assertEquals(30000 + 20000, $order->total);

        // Pemilik menyetujui
        $this->actingAs($owner)->post("/transaksi/{$order->id}/respon", ['decision' => 'setuju']);
        $this->assertSame('menunggu_pembayaran', $order->fresh()->status);

        // Peminjam memilih QRIS lalu membayar
        $this->actingAs($renter)->post("/transaksi/{$order->id}/pembayaran", ['method' => 'qris'])->assertRedirect();
        $payment = Payment::firstOrFail();
        $this->actingAs($renter)->get("/pembayaran/{$payment->id}")->assertOk();
        $this->actingAs($renter)->post("/pembayaran/{$payment->id}/bayar")->assertRedirect("/pembayaran/{$payment->id}/berhasil");
        $this->assertSame('dibayar', $order->fresh()->status);
        $this->assertSame('held', $order->fresh()->escrow_status);

        // Serah terima, pengembalian, konfirmasi
        $this->actingAs($owner)->post("/transaksi/{$order->id}/serahkan", [
            'photo' => UploadedFile::fake()->create('serah-terima.jpg', 100, 'image/jpeg'),
        ]);
        $this->actingAs($renter)->post("/transaksi/{$order->id}/dikembalikan", [
            'photo' => UploadedFile::fake()->create('pengembalian.jpg', 100, 'image/jpeg'),
        ]);
        $admin = $this->member();
        $admin->forceFill(['role' => 'admin'])->save();
        $this->actingAs($admin)->patch(route('admin.payouts.release', $order))->assertUnprocessable();
        $this->assertSame('pengembalian', $order->fresh()->status);
        $this->actingAs($owner)->post("/transaksi/{$order->id}/konfirmasi-kembali");
        $this->assertSame('menunggu_persetujuan_admin', $order->fresh()->status);
        $this->actingAs($renter)->patch(route('admin.payouts.release', $order))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.payouts'))->assertOk()
            ->assertSee($item->title)
            ->assertSee('Biaya sewa yang akan dicairkan')
            ->assertSee('Deposit yang akan dikembalikan');
        $this->actingAs($admin)->patch(route('admin.payouts.release', $order))->assertRedirect();
        $this->assertSame('selesai', $order->fresh()->status);
        $this->assertSame('released', $order->fresh()->escrow_status);
        $this->assertSame($admin->id, $order->fresh()->funds_released_by);
        $this->assertNotNull($order->fresh()->funds_released_at);
        $this->assertSame(30000, $owner->fresh()->balance);
        $this->assertSame(20000, $renter->fresh()->balance);

        // Ulasan hanya satu per pihak
        $this->actingAs($renter)->post("/transaksi/{$order->id}/ulasan", ['rating' => 5, 'comment' => 'Mantap'])->assertRedirect();
        $this->actingAs($renter)->post("/transaksi/{$order->id}/ulasan", ['rating' => 1])->assertRedirect();
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_wanted_offer_only_accepts_matching_item_type(): void
    {
        $requester = $this->member();
        $owner = $this->member();
        $rentItem = $this->item($owner, ['type' => 'sewa', 'status' => 'tersedia']);
        $saleItem = $this->item($owner, ['type' => 'beli', 'deposit' => 0, 'status' => 'tersedia']);

        $rentWanted = WantedRequest::create([
            'user_id' => $requester->id,
            'title' => 'Butuh kalkulator',
            'mode' => 'sewa',
            'status' => 'aktif',
        ]);

        $this->actingAs($owner)->post("/dicari/{$rentWanted->id}/tawaran", [
            'item_id' => $saleItem->id,
            'message' => 'Saya punya barang jual',
        ])->assertSessionHasErrors('item_id');

        $this->actingAs($owner)->post("/dicari/{$rentWanted->id}/tawaran", [
            'item_id' => $rentItem->id,
            'message' => 'Saya punya untuk disewa',
        ])->assertRedirect();

        $this->assertDatabaseCount('wanted_offers', 1);
    }

    public function test_overlapping_rent_dates_are_rejected(): void
    {
        $owner = $this->member();
        $item = $this->item($owner);
        $a = $this->member();
        $b = $this->member();
        $start = now()->addDays(2)->toDateString();
        $end = now()->addDays(4)->toDateString();

        $this->actingAs($a)->post("/barang/{$item->id}/pesan", ['pickup_method' => 'ambil_kampus', 'start_date' => $start, 'end_date' => $end]);

        // Permintaan yang belum disetujui belum mengunci tanggal; setelah disetujui, tanggal terkunci.
        $first = Order::firstOrFail();
        $this->actingAs($owner)->post("/transaksi/{$first->id}/respon", ['decision' => 'setuju']);

        $this->actingAs($b)->post("/barang/{$item->id}/pesan", ['pickup_method' => 'ambil_kampus', 'start_date' => $start, 'end_date' => $end])
            ->assertSessionHasErrors('start_date');

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_cannot_order_own_item_and_sale_item_is_reserved(): void
    {
        $owner = $this->member();
        $buyer = $this->member();
        $other = $this->member();
        $sale = $this->item($owner, ['type' => 'beli', 'price' => 50000, 'deposit' => 0]);

        $this->actingAs($owner)->post("/barang/{$sale->id}/pesan", ['pickup_method' => 'ambil_kampus'])->assertForbidden();

        $this->actingAs($buyer)->post("/barang/{$sale->id}/pesan", ['pickup_method' => 'ambil_kampus'])->assertRedirect();
        $this->assertSame('dipesan', $sale->fresh()->status);

        $this->actingAs($other)->post("/barang/{$sale->id}/pesan", ['pickup_method' => 'ambil_kampus'])->assertStatus(409);
    }

    public function test_strangers_cannot_open_someone_elses_order_and_blocked_users_are_hidden(): void
    {
        $owner = $this->member();
        $buyer = $this->member();
        $stranger = $this->member();
        $item = $this->item($owner, ['type' => 'beli', 'price' => 50000, 'deposit' => 0]);

        $this->actingAs($buyer)->post("/barang/{$item->id}/pesan", ['pickup_method' => 'ambil_kampus']);
        $order = Order::firstOrFail();

        $this->actingAs($stranger)->get("/transaksi/{$order->id}")->assertForbidden();

        $stranger->blockedUsers()->attach($owner->id);
        $this->actingAs($stranger)->get("/barang/{$item->id}")->assertNotFound();
    }
}
