<?php

use App\Models\PricingModel;
use App\Models\VendorType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pricing_models', function (Blueprint $table) {
            $table->foreignId('vendor_type_id')->nullable()->after('slug')->constrained('vendor_types')->nullOnDelete();
        });

        foreach (PricingModel::query()->get() as $model) {
            $typeId = VendorType::query()->where('pricing_model_id', $model->id)->value('id');
            if ($typeId) {
                $model->update(['vendor_type_id' => $typeId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pricing_models', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_type_id');
        });
    }
};
