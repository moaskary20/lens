<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->json('app_settings')->nullable()->after('city_id');
        });

        Schema::create('user_payment_methods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('label')->nullable();
            $table->json('details')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('user_addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label')->default('Home');
            $table->string('line');
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
        Schema::dropIfExists('user_payment_methods');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('app_settings');
        });
    }
};
