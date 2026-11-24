<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->string('code', 24)->unique();
            $t->foreignId('item_id')->constrained('items');
            $t->foreignId('buyer_id')->constrained('users');   // peminjam / pembeli
            $t->foreignId('seller_id')->constrained('users');  // pemilik barang
            $t->string('type', 10);                            // beli | sewa
            $t->date('start_date')->nullable();
            $t->date('end_date')->nullable();
            $t->unsignedSmallInteger('days')->default(1);
            $t->unsignedBigInteger('price');                   // harga satuan saat transaksi
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('deposit')->default(0);
            $t->unsignedBigInteger('total');
            $t->string('pickup_method', 20)->default('ambil_kampus');
            $t->text('note')->nullable();
            $t->string('status', 30)->default('menunggu_pembayaran');
            $t->timestamp('handed_over_at')->nullable();
            $t->timestamp('returned_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();

            $t->index(['buyer_id', 'status']);
            $t->index(['seller_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->string('method', 10);                          // qris | ewallet | bank
            $t->string('provider', 20)->nullable();            // gopay, ovo, dana, shopeepay, bca, ...
            $t->string('account_number', 30)->nullable();      // nomor virtual account
            $t->string('phone', 20)->nullable();               // nomor e-wallet
            $t->string('reference', 30)->unique();
            $t->unsignedBigInteger('amount');
            $t->string('status', 10)->default('pending');      // pending | paid | expired
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });

        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating');
            $t->text('comment')->nullable();
            $t->timestamps();

            $t->unique(['order_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
    }
};
