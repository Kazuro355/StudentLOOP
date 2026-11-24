<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('category_id')->constrained('categories');
            $t->string('type', 10);                       // beli | sewa
            $t->string('title', 120);
            $t->text('description');
            $t->string('condition', 30);
            $t->string('kelengkapan')->nullable();
            $t->unsignedBigInteger('price');              // harga jual atau harga sewa per hari
            $t->unsignedBigInteger('deposit')->default(0);
            $t->unsignedSmallInteger('min_days')->default(1);
            $t->unsignedSmallInteger('max_days')->default(14);
            $t->string('pickup_method', 20)->default('ambil_kampus');
            $t->string('meeting_point')->nullable();
            $t->string('kampus', 40);
            $t->string('status', 20)->default('tersedia'); // tersedia | dipesan | terjual | nonaktif
            $t->unsignedInteger('views')->default(0);
            $t->timestamps();

            $t->index(['status', 'type']);
            $t->index('kampus');
        });

        Schema::create('item_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->string('path');
            $t->unsignedTinyInteger('position')->default(0);
            $t->timestamps();
        });

        Schema::create('favorites', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->timestamps();
            $t->primary(['user_id', 'item_id']);
        });

        Schema::create('item_questions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->text('body');
            $t->text('answer')->nullable();
            $t->timestamp('answered_at')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_questions');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('item_photos');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
    }
};
