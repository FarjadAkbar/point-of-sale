<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('expense_categories')->restrictOnDelete();
            $table->string('name');
            $table->string('code', 64);
            $table->timestamps();

            $table->unique(['team_id', 'code']);
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_location_id')->constrained('business_locations')->restrictOnDelete();
            $table->foreignId('expense_category_id')->nullable()->constrained('expense_categories')->nullOnDelete();
            $table->string('ref_no')->nullable();
            $table->dateTime('transaction_date');
            $table->foreignId('expense_for_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('document_path')->nullable();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('tax_amount', 15, 4)->default(0);
            $table->decimal('final_total', 15, 4);
            $table->text('additional_notes')->nullable();
            $table->boolean('is_refund')->default(false);
            $table->boolean('is_recurring')->default(false);
            $table->unsignedInteger('recur_interval')->nullable();
            $table->string('recur_interval_type', 16)->nullable();
            $table->unsignedInteger('recur_repetitions')->nullable();
            $table->unsignedTinyInteger('subscription_repeat_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('expense_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 4);
            $table->dateTime('paid_on');
            $table->string('method', 32);
            $table->foreignId('payment_account_id')->nullable()->constrained('payment_accounts')->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_payments');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
