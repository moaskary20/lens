<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->string('payout_method')->nullable()->after('transfer_notes');
            $table->string('bank_swift')->nullable()->after('payout_method');
            $table->string('bank_branch')->nullable()->after('bank_swift');
            $table->string('bank_branch_code')->nullable()->after('bank_branch');
            $table->string('bank_account_type')->nullable()->after('bank_branch_code');
            $table->string('wallet_network_type')->nullable()->after('bank_account_type');
            $table->string('wallet_telecom')->nullable()->after('wallet_network_type');
            $table->string('wallet_bank_name')->nullable()->after('wallet_telecom');
            $table->string('wallet_phone')->nullable()->after('wallet_bank_name');
            $table->string('paypal_email')->nullable()->after('wallet_phone');
            $table->string('paypal_name')->nullable()->after('paypal_email');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn([
                'payout_method',
                'bank_swift',
                'bank_branch',
                'bank_branch_code',
                'bank_account_type',
                'wallet_network_type',
                'wallet_telecom',
                'wallet_bank_name',
                'wallet_phone',
                'paypal_email',
                'paypal_name',
            ]);
        });
    }
};
