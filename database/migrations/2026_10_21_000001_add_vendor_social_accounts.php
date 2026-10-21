<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedInteger('instagram_followers')->nullable()->after('instagram');
            $table->string('tiktok_url')->nullable()->after('instagram_followers');
            $table->unsignedInteger('tiktok_followers')->nullable()->after('tiktok_url');
            $table->string('facebook_url')->nullable()->after('tiktok_followers');
            $table->unsignedInteger('facebook_followers')->nullable()->after('facebook_url');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'instagram_followers',
                'tiktok_url',
                'tiktok_followers',
                'facebook_url',
                'facebook_followers',
            ]);
        });
    }
};
