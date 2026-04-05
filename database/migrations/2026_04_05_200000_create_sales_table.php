<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('business_location_id')->constrained('business_locations')->restrictOnDelete();
            $table->string('invoice_no')->nullable();
            $table->dateTime('transaction_date');
            $table->string('status', 32);
            $table->unsignedSmallInteger('pay_term_number')->nullable();
            $table->string('pay_term_type', 16)->nullable();
            $table->string('discount_type', 16)->default('none');
            $table->decimal('discount_amount', 15, 4)->default(0);
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('sale_tax_amount', 15, 4)->default(0);
            $table->string('shipping_details')->nullable();
            $table->decimal('shipping_charges', 15, 4)->default(0);
            $table->text('shipping_address')->nullable();
            $table->json('additional_expenses')->nullable();
            $table->text('sale_note')->nullable();
            $table->string('document_path')->nullable();
            $table->decimal('lines_total', 15, 4)->default(0);
            $table->decimal('final_total', 15, 4);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
