<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('party_role', 20);
            $table->string('entity_type', 20);
            $table->string('customer_code')->nullable();
            $table->foreignId('customer_group_id')->nullable()->constrained('customer_groups')->nullOnDelete();
            $table->string('business_name')->nullable();
            $table->string('prefix')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('mobile');
            $table->string('alternate_number')->nullable();
            $table->string('landline')->nullable();
            $table->string('email')->nullable();
            $table->date('dob')->nullable();
            $table->string('tax_number')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('credit_limit', 15, 2)->nullable();
            $table->unsignedSmallInteger('pay_term_number')->nullable();
            $table->string('pay_term_type', 20)->nullable();
            $table->text('address_line_1')->nullable();
            $table->text('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('land_mark')->nullable();
            $table->string('street_name')->nullable();
            $table->string('building_number')->nullable();
            $table->string('additional_number')->nullable();
            $table->text('shipping_address')->nullable();
            for ($i = 1; $i <= 10; $i++) {
                $table->string("custom_field{$i}")->nullable();
            }
            $table->timestamps();
            $table->softDeletes();

            $table->index(['team_id', 'created_at']);
            $table->unique(['team_id', 'customer_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
