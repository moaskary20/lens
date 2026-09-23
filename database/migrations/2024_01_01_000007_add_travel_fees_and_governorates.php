<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('governorate')->nullable()->after('country');
        });

        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('accepts_out_of_governorate')->default(false)->after('is_featured');
            $table->decimal('default_travel_fee', 10, 2)->default(0)->after('accepts_out_of_governorate');
        });

        Schema::create('vendor_travel_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('destination_governorate');
            $table->decimal('fee', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['vendor_id', 'destination_governorate']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('location_text')->constrained()->nullOnDelete();
            $table->decimal('travel_fee', 10, 2)->default(0)->after('session_price');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
            $table->dropColumn('travel_fee');
        });

        Schema::dropIfExists('vendor_travel_rates');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['accepts_out_of_governorate', 'default_travel_fee']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('governorate');
        });
    }
};
