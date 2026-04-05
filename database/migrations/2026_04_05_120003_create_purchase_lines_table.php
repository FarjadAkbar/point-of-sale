<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost_before_discount', 15, 4);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('unit_cost_exc_tax', 15, 4);
            $table->decimal('product_tax_percent', 8, 4)->default(0);
            $table->decimal('line_subtotal_exc_tax', 15, 4);
            $table->decimal('line_tax_amount', 15, 4);
            $table->decimal('line_total', 15, 4);
            $table->decimal('profit_margin_percent', 8, 4)->nullable();
            $table->decimal('unit_selling_price_inc_tax', 15, 4)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_lines');
    }
};
