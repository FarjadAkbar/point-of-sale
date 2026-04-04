<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('barcode_type', 40)->nullable();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->json('business_location_ids')->nullable();
            $table->boolean('manage_stock')->default(false);
            $table->decimal('alert_quantity', 15, 4)->nullable();
            $table->longText('description')->nullable();
            $table->string('image_path')->nullable();
            $table->string('brochure_path')->nullable();
            $table->boolean('enable_imei_serial')->default(false);
            $table->boolean('not_for_selling')->default(false);
            $table->decimal('weight', 12, 4)->nullable();
            $table->unsignedSmallInteger('preparation_time_minutes')->nullable();
            $table->string('application_tax', 50)->nullable();
            $table->string('selling_price_tax_type', 20)->default('exclusive');
            $table->string('product_type', 20)->default('single');

            $table->decimal('single_dpp', 15, 4)->nullable();
            $table->decimal('single_dpp_inc_tax', 15, 4)->nullable();
            $table->decimal('profit_percent', 10, 4)->nullable();
            $table->decimal('single_dsp', 15, 4)->nullable();
            $table->decimal('single_dsp_inc_tax', 15, 4)->nullable();

            $table->decimal('combo_profit_percent', 10, 4)->nullable();
            $table->decimal('combo_selling_price', 15, 4)->nullable();
            $table->decimal('combo_selling_price_inc_tax', 15, 4)->nullable();
            $table->json('combo_lines')->nullable();
            $table->decimal('combo_purchase_total_exc_tax', 15, 4)->nullable();
            $table->decimal('combo_purchase_total_inc_tax', 15, 4)->nullable();

            $table->string('variation_sku_format', 40)->nullable();
            $table->json('variation_matrix')->nullable();

            $table->timestamps();

            $table->index(['team_id', 'name']);
            $table->unique(['team_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
