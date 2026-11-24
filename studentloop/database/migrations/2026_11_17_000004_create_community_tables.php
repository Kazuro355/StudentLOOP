<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wanted_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $t->string('title', 120);
            $t->text('details')->nullable();
            $t->string('mode', 10)->default('sewa');           // beli | sewa
            $t->unsignedBigInteger('budget_min')->nullable();
            $t->unsignedBigInteger('budget_max')->nullable();
            $t->date('needed_by')->nullable();
            $t->string('kampus', 40)->nullable();
            $t->string('status', 15)->default('aktif');        // aktif | terpenuhi | ditutup
            $t->timestamps();
        });

        Schema::create('wanted_offers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('wanted_request_id')->constrained('wanted_requests')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $t->text('message');
            $t->unsignedBigInteger('price')->nullable();
            $t->string('status', 15)->default('menunggu');     // menunggu | diterima | ditolak
            $t->timestamps();
        });

        Schema::create('conversations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->nullable()->constrained('items')->nullOnDelete();
            $t->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $t->timestamp('last_message_at')->nullable();
            $t->timestamps();
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $t->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        Schema::create('user_notifications', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('type', 30);                            // transaksi | pembayaran | pengembalian | ulasan | wanted | keamanan
            $t->string('title');
            $t->text('body')->nullable();
            $t->string('url')->nullable();
            $t->timestamp('read_at')->nullable();
            $t->timestamps();

            $t->index(['user_id', 'read_at']);
        });

        Schema::create('reports', function (Blueprint $t) {
            $t->id();
            $t->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $t->string('target_type', 10);                     // item | user | order
            $t->unsignedBigInteger('target_id');
            $t->string('reason', 60);
            $t->text('details')->nullable();
            $t->string('status', 10)->default('baru');         // baru | diproses | selesai
            $t->timestamps();
        });

        Schema::create('user_blocks', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('blocked_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->primary(['user_id', 'blocked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('wanted_offers');
        Schema::dropIfExists('wanted_requests');
    }
};
