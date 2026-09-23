<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('available', 12, 2)->default(0);
            $table->decimal('pending', 12, 2)->default(0);
            $table->decimal('coupon_credit', 12, 2)->default(0);
            $table->decimal('lifetime_earned', 12, 2)->default(0);
            $table->decimal('lifetime_withdrawn', 12, 2)->default(0);
            $table->decimal('lifetime_commission', 12, 2)->default(0);
            $table->decimal('lifetime_paid', 12, 2)->default(0);
            $table->decimal('lifetime_refunded', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label')->nullable();
            $table->string('type')->default('fixed');
            $table->decimal('amount', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('max_uses')->default(1);
            $table->unsignedInteger('uses_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->decimal('amount', 12, 2);
            $table->decimal('available_delta', 12, 2)->default(0);
            $table->decimal('pending_delta', 12, 2)->default(0);
            $table->decimal('coupon_delta', 12, 2)->default(0);
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['wallet_id', 'type']);
            $table->index(['booking_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('wallets');
    }
};
