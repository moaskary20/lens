<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->string('campaign')->default('coupon')->after('label');
            $table->foreignId('vendor_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->foreignId('vendor_type_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->timestamp('starts_at')->nullable()->after('uses_count');
            $table->unsignedInteger('min_completed_bookings')->default(0)->after('starts_at');
            $table->unsignedInteger('max_uses_per_user')->default(1)->after('min_completed_bookings');
            $table->boolean('auto_apply')->default(false)->after('is_active');
        });

        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'user_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('discount_amount', 12, 2)->default(0);
        });

        DB::table('coupons')->where('type', 'fixed')->update([
            'type' => 'wallet_credit',
        ]);

        $assigned = DB::table('coupons')->whereNotNull('user_id')->get(['id', 'user_id']);
        foreach ($assigned as $coupon) {
            DB::table('coupon_user')->insertOrIgnore([
                'coupon_id' => $coupon->id,
                'user_id' => $coupon->user_id,
            ]);
            DB::table('coupons')->where('id', $coupon->id)->update(['campaign' => 'user']);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('discount_amount');
        });

        Schema::dropIfExists('coupon_user');

        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
            $table->dropConstrainedForeignId('vendor_type_id');
            $table->dropColumn([
                'campaign', 'starts_at', 'min_completed_bookings', 'max_uses_per_user', 'auto_apply',
            ]);
        });
    }
};
