<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('member');
            $table->timestamp('suspended_at')->nullable()->index();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->timestamp('moderated_at')->nullable()->index();
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('resolution')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['reviewed_at', 'resolution']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('moderated_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'suspended_at']);
        });
    }
};
