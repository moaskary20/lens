<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('client_brief')->nullable()->after('notes');
        });

        Schema::create('moodboards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->text('brief')->nullable();
            $table->json('payload')->nullable();
            $table->string('provider')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moodboards');
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('client_brief');
        });
    }
};
