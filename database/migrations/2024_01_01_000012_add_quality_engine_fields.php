<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('penalty_total', 10, 2)->default(0)->after('failed_sessions');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unique('booking_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('penalty_total');
        });
    }
};
