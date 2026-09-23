<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('profile_photo')->nullable()->after('cover_image');
            $table->string('national_id_image')->nullable()->after('profile_photo');
            $table->date('date_of_birth')->nullable()->after('national_id_image');
            $table->string('profession')->nullable()->after('date_of_birth');
            $table->string('contact_phone')->nullable()->after('profession');
            $table->string('contact_email')->nullable()->after('contact_phone');
            $table->string('whatsapp')->nullable()->after('contact_email');
            $table->string('instagram')->nullable()->after('whatsapp');
            $table->string('bank_name')->nullable()->after('instagram');
            $table->string('bank_account_holder')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_holder');
            $table->string('bank_iban')->nullable()->after('bank_account_number');
            $table->string('instapay')->nullable()->after('bank_iban');
            $table->text('transfer_notes')->nullable()->after('instapay');
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'profile_photo', 'national_id_image', 'date_of_birth', 'profession',
                'contact_phone', 'contact_email', 'whatsapp', 'instagram',
                'bank_name', 'bank_account_holder', 'bank_account_number', 'bank_iban',
                'instapay', 'transfer_notes',
            ]);
        });
    }
};
