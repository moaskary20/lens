<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('replacement_offers');
    }

    public function down(): void
    {
        Schema::create('replacement_offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('original_vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->foreignId('suggested_vendor_id')->nullable()->constrained('vendors')->nullOnDelete();
            $table->string('status')->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
