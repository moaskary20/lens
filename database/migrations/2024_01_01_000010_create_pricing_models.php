<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pricing_models', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('pricing_model_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_model_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('storage_key')->nullable();
            $table->string('label');
            $table->string('package_type');
            $table->string('unit')->default('session');
            $table->unsignedInteger('duration_hours')->nullable();
            $table->string('helper_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('vendor_types', function (Blueprint $table) {
            $table->foreignId('pricing_model_id')->nullable()->after('pricing_model')->constrained('pricing_models')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pricing_model_id');
        });
        Schema::dropIfExists('pricing_model_fields');
        Schema::dropIfExists('pricing_models');
    }
};
