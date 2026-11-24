<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WantedController;
use App\Http\Middleware\EnsureProfileComplete;
use App\Http\Middleware\EnsureAdmin;
use App\Http\Middleware\EnsureUserActive;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => Auth::check() ? redirect()->route('beranda') : redirect()->route('login'));

/* ---------- Akun (tanpa verifikasi email) ---------- */
Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->name('login.post');
    Route::get('/daftar', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->name('register.post');
    Route::get('/lupa-kata-sandi', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/lupa-kata-sandi', [AuthController::class, 'sendReset'])->name('password.email');
    Route::get('/reset-kata-sandi/{token}', [AuthController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-kata-sandi', [AuthController::class, 'reset'])->name('password.update');
});

Route::middleware(['auth', EnsureUserActive::class])->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/lengkapi-profil', [ProfileController::class, 'complete'])->name('profile.complete');
    Route::post('/lengkapi-profil', [ProfileController::class, 'storeComplete'])->name('profile.complete.store');
});

Route::middleware(['auth', EnsureUserActive::class, EnsureAdmin::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::get('/laporan', [AdminController::class, 'reports'])->name('reports');
        Route::patch('/laporan/{report}/status', [AdminController::class, 'updateReportStatus'])->name('reports.status');
        Route::patch('/laporan/{report}/moderasi', [AdminController::class, 'resolveReport'])->name('reports.resolve');
        Route::get('/pencairan', [AdminController::class, 'payouts'])->name('payouts');
        Route::patch('/pencairan/{order}', [AdminController::class, 'releaseFunds'])->name('payouts.release');
        Route::get('/pengguna', [AdminController::class, 'users'])->name('users');
        Route::patch('/pengguna/{user}/akses', [AdminController::class, 'updateUserSuspension'])->name('users.suspension');
    });

/* ---------- Aplikasi (wajib login + profil lengkap) ---------- */
Route::middleware(['auth', EnsureUserActive::class, EnsureProfileComplete::class])->group(function () {

    // Jelajah & pencarian
    Route::get('/beranda', [HomeController::class, 'index'])->name('beranda');
    Route::get('/terpopuler', [HomeController::class, 'section'])->defaults('key', 'popular')->name('items.popular');
    Route::get('/baru-ditambahkan', [HomeController::class, 'section'])->defaults('key', 'newest')->name('items.newest');
    Route::get('/di-dekatmu', [HomeController::class, 'section'])->defaults('key', 'nearby')->name('items.nearby');
    Route::get('/rekomendasi', [HomeController::class, 'section'])->defaults('key', 'recommended')->name('items.recommended');
    Route::get('/panduan-pencarian', [HomeController::class, 'guide'])->name('search.guide');
    Route::get('/cari', [HomeController::class, 'search'])->name('search');

    // Favorit
    Route::get('/favorit', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorit/{item}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // Barang
    Route::get('/barang-saya', [ItemController::class, 'mine'])->name('items.mine');
    Route::get('/jual-barang', [ItemController::class, 'create'])->defaults('type', 'beli')->name('items.create.sell');
    Route::get('/sewakan-barang', [ItemController::class, 'create'])->defaults('type', 'sewa')->name('items.create.rent');
    Route::post('/barang', [ItemController::class, 'store'])->name('items.store');
    Route::get('/barang/{item}', [ItemController::class, 'show'])->name('items.show');
    Route::get('/barang/{item}/ubah', [ItemController::class, 'edit'])->name('items.edit');
    Route::put('/barang/{item}', [ItemController::class, 'update'])->name('items.update');
    Route::post('/barang/{item}/status', [ItemController::class, 'toggle'])->name('items.toggle');
    Route::delete('/barang/{item}', [ItemController::class, 'destroy'])->name('items.destroy');
    Route::post('/barang/{item}/pertanyaan', [QuestionController::class, 'store'])->name('questions.store');
    Route::post('/pertanyaan/{question}/jawab', [QuestionController::class, 'answer'])->name('questions.answer');

    // Sewa, beli, transaksi
    Route::get('/barang/{item}/sewa', [OrderController::class, 'rent'])->name('orders.rent');
    Route::get('/barang/{item}/beli', [OrderController::class, 'buy'])->name('orders.buy');
    Route::post('/barang/{item}/pesan', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/transaksi', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pinjaman-saya', [OrderController::class, 'index'])->defaults('preset', 'pinjaman')->name('orders.loans');
    Route::get('/transaksi/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/transaksi/{order}/respon', [OrderController::class, 'respond'])->name('orders.respond');
    Route::post('/transaksi/{order}/batal', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::post('/transaksi/{order}/serahkan', [OrderController::class, 'handover'])->name('orders.handover');
    Route::post('/transaksi/{order}/dikembalikan', [OrderController::class, 'returned'])->name('orders.returned');
    Route::post('/transaksi/{order}/konfirmasi-kembali', [OrderController::class, 'confirmReturn'])->name('orders.confirmReturn');
    Route::post('/transaksi/{order}/diterima', [OrderController::class, 'received'])->name('orders.received');

    // Pembayaran (QRIS / e-wallet / transfer bank)
    Route::get('/transaksi/{order}/pembayaran', [PaymentController::class, 'choose'])->name('payments.choose');
    Route::post('/transaksi/{order}/pembayaran', [PaymentController::class, 'store'])->name('payments.store');
    Route::get('/pembayaran/{payment}', [PaymentController::class, 'show'])->name('payments.show');
    Route::post('/pembayaran/{payment}/bayar', [PaymentController::class, 'pay'])->name('payments.pay');
    Route::get('/pembayaran/{payment}/berhasil', [PaymentController::class, 'success'])->name('payments.success');

    // Ulasan
    Route::get('/transaksi/{order}/ulasan', [ReviewController::class, 'create'])->name('reviews.create');
    Route::post('/transaksi/{order}/ulasan', [ReviewController::class, 'store'])->name('reviews.store');

    // Wanted (cari barang yang dibutuhkan)
    Route::get('/dicari', [WantedController::class, 'index'])->name('wanted.index');
    Route::get('/dicari/buat', [WantedController::class, 'create'])->name('wanted.create');
    Route::post('/dicari', [WantedController::class, 'store'])->name('wanted.store');
    Route::get('/dicari/{wanted}', [WantedController::class, 'show'])->name('wanted.show');
    Route::post('/dicari/{wanted}/tutup', [WantedController::class, 'close'])->name('wanted.close');
    Route::post('/dicari/{wanted}/tawaran', [WantedController::class, 'offer'])->name('wanted.offer');
    Route::post('/tawaran/{offer}/respon', [WantedController::class, 'respond'])->name('wanted.respond');

    // Pesan / chat
    Route::get('/pesan', [ConversationController::class, 'index'])->name('conversations.index');
    Route::get('/pesan/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/pesan/{conversation}', [ConversationController::class, 'send'])->name('messages.store');
    Route::get('/barang/{item}/chat', [ConversationController::class, 'startForItem'])->name('conversations.item');
    Route::get('/pengguna/{user}/chat', [ConversationController::class, 'startWithUser'])->name('conversations.user');

    // Notifikasi
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifikasi/{notification}', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifikasi-baca-semua', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Profil, laporan & keamanan
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profil/ubah', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profil/kata-sandi', [ProfileController::class, 'password'])->name('profile.password');
    Route::get('/pengguna/{user}', [ProfileController::class, 'publicProfile'])->name('users.show');
    Route::post('/pengguna/{user}/blokir', [ReportController::class, 'block'])->name('users.block');
    Route::delete('/pengguna/{user}/blokir', [ReportController::class, 'unblock'])->name('users.unblock');
    Route::get('/laporkan', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/laporkan', [ReportController::class, 'store'])->name('reports.store');
});
