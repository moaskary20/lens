<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('filter_groups', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('scope')->default('all');
            $table->string('facet_level')->default('secondary');
            $table->string('input_type')->default('checkbox');
            $table->json('vendor_type_slugs')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('filter_tags', function (Blueprint $table) {
            $table->foreignId('filter_group_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('helper_text')->nullable()->after('name_en');
            $table->json('synonyms')->nullable()->after('vendor_type_slugs');
            $table->string('unit')->nullable()->after('synonyms');
            $table->decimal('min_value', 10, 2)->nullable()->after('unit');
            $table->decimal('max_value', 10, 2)->nullable()->after('min_value');
            $table->boolean('show_in_quick_filters')->default(false)->after('is_active');
        });

        Schema::create('recommendation_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trigger_type');
            $table->string('trigger_value');
            $table->foreignId('suggest_vendor_type_id')->nullable()->constrained('vendor_types')->nullOnDelete();
            $table->json('suggest_filter_tag_ids')->nullable();
            $table->string('suggest_message')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendation_rules');

        Schema::table('filter_tags', function (Blueprint $table) {
            $table->dropConstrainedForeignId('filter_group_id');
            $table->dropColumn([
                'helper_text', 'synonyms', 'unit', 'min_value', 'max_value', 'show_in_quick_filters',
            ]);
        });

        Schema::dropIfExists('filter_groups');
    }
};
