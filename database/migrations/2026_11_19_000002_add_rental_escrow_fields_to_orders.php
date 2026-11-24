<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('escrow_status', 20)->default('none');
            $table->timestamp('funds_released_at')->nullable();
            $table->foreignId('funds_released_by')->nullable()->constrained('users')->nullOnDelete();
        });

        \Illuminate\Support\Facades\DB::table('orders')
            ->where('type', 'sewa')
            ->whereIn('status', ['dibayar', 'diserahkan', 'pengembalian'])
            ->update(['escrow_status' => 'held']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('funds_released_by');
            $table->dropColumn(['escrow_status', 'funds_released_at']);
        });
    }
};
