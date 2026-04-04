<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->string('price_calculation_type', 40)->default('percentage')->after('name');
            $table->decimal('amount', 10, 4)->nullable()->after('price_calculation_type');
            $table->foreignId('selling_price_group_id')->nullable()->after('amount')->constrained('selling_price_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('customer_groups', function (Blueprint $table) {
            $table->dropConstrainedForeignId('selling_price_group_id');
            $table->dropColumn(['price_calculation_type', 'amount']);
        });
    }
};
