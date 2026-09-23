<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('country')->default('SA');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('vendor_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('icon')->nullable();
            $table->string('pricing_model')->default('half_full_day');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('escrow_on_checkin')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('filter_tags', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('group_key');
            $table->json('vendor_type_slugs')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->text('criteria')->nullable();
            $table->boolean('is_automatic')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('display_name');
            $table->text('bio')->nullable();
            $table->string('cover_image')->nullable();
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('verification_status')->default('pending');
            $table->text('verification_notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->decimal('half_day_price', 10, 2)->nullable();
            $table->decimal('full_day_price', 10, 2)->nullable();
            $table->decimal('hourly_price', 10, 2)->nullable();
            $table->decimal('per_video_price', 10, 2)->nullable();
            $table->unsignedInteger('turnaround_hours')->nullable();
            $table->json('delivery_formats')->nullable();
            $table->json('equipment')->nullable();
            $table->json('specialties')->nullable();
            $table->json('extras')->nullable();
            $table->unsignedInteger('booked_sessions')->default(0);
            $table->unsignedInteger('completed_sessions')->default(0);
            $table->unsignedInteger('accepted_sessions')->default(0);
            $table->unsignedInteger('rejected_sessions')->default(0);
            $table->unsignedInteger('failed_sessions')->default(0);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->unsignedInteger('response_minutes')->nullable();
            $table->timestamps();
        });

        Schema::create('vendor_filter_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('filter_tag_id')->constrained()->cascadeOnDelete();
            $table->unique(['vendor_id', 'filter_tag_id']);
        });

        Schema::create('category_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id', 'vendor_id']);
        });

        Schema::create('badge_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unique(['badge_id', 'vendor_id']);
        });

        Schema::create('portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('image');
            $table->string('path');
            $table->string('title')->nullable();
            $table->string('external_url')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('pending');
            $table->dateTime('scheduled_at')->nullable();
            $table->unsignedInteger('duration_hours')->nullable();
            $table->string('package_type')->nullable();
            $table->string('location_text')->nullable();
            $table->decimal('session_price', 10, 2)->default(0);
            $table->decimal('client_fee', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('vendor_commission', 10, 2)->default(0);
            $table->decimal('vendor_net', 10, 2)->default(0);
            $table->string('escrow_status')->default('none');
            $table->string('payout_status')->default('none');
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedInteger('revision_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('escrow_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending');
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('open');
            $table->text('reason');
            $table->decimal('client_refund_percent', 5, 2)->nullable();
            $table->decimal('vendor_payout_percent', 5, 2)->nullable();
            $table->decimal('platform_fee_percent', 5, 2)->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->boolean('is_watermarked')->default(true);
            $table->boolean('is_unlocked')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_flagged')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_screens', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('image')->nullable();
            $table->string('cta_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('cms_pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('actor');
            $table->string('name');
            $table->unsignedInteger('min_hours')->nullable();
            $table->unsignedInteger('max_hours')->nullable();
            $table->decimal('client_refund_percent', 5, 2)->default(0);
            $table->decimal('vendor_payout_percent', 5, 2)->default(0);
            $table->decimal('platform_fee_percent', 5, 2)->default(0);
            $table->decimal('vendor_penalty_percent', 5, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
        Schema::dropIfExists('cms_pages');
        Schema::dropIfExists('app_screens');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('deliverables');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('escrow_transactions');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('portfolios');
        Schema::dropIfExists('badge_vendor');
        Schema::dropIfExists('category_vendor');
        Schema::dropIfExists('vendor_filter_tag');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('filter_tags');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('vendor_types');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('settings');
    }
};
