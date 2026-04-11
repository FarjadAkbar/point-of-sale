<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('shipping_status', 64)->nullable()->after('shipping_address');
            $table->string('delivered_to')->nullable()->after('shipping_status');
            $table->string('delivery_person')->nullable()->after('delivered_to');
            $table->text('shipping_customer_note')->nullable()->after('delivery_person');
            $table->string('shipping_document_path')->nullable()->after('shipping_customer_note');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_status',
                'delivered_to',
                'delivery_person',
                'shipping_customer_note',
                'shipping_document_path',
            ]);
        });
    }
};
