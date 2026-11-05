<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedSmallInteger('years_experience')->default(0)->after('profession');
            $table->unsignedInteger('assisted_sessions')->default(0)->after('completed_sessions');
            $table->unsignedTinyInteger('client_satisfaction')->default(0)->after('rating_count');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['years_experience', 'assisted_sessions', 'client_satisfaction']);
        });
    }
};
