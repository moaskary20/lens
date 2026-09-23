<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->string('project_name')->nullable()->after('notes');
            $table->string('project_type')->nullable()->after('project_name');
            $table->decimal('location_lat', 10, 7)->nullable()->after('location_text');
            $table->decimal('location_lng', 10, 7)->nullable()->after('location_lat');
            $table->string('payment_method')->nullable()->after('coupon_id');
            $table->json('project_details')->nullable()->after('client_brief');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'project_name',
                'project_type',
                'location_lat',
                'location_lng',
                'payment_method',
                'project_details',
            ]);
        });
    }
};
