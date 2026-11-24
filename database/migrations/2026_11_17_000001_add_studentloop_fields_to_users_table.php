<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('nim', 20)->nullable()->unique()->after('email');
            $t->string('jurusan')->nullable();
            $t->string('angkatan', 4)->nullable();
            $t->string('kampus')->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('area')->nullable();
            $t->text('bio')->nullable();
            $t->timestamp('profile_completed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropUnique(['nim']);
            $t->dropColumn(['nim', 'jurusan', 'angkatan', 'kampus', 'phone', 'area', 'bio', 'profile_completed_at']);
        });
    }
};
