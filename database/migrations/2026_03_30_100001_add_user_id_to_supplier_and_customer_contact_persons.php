<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_contact_persons', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('supplier_id')->constrained('users')->nullOnDelete();
        });

        Schema::table('customer_contact_persons', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('customer_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_contact_persons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('customer_contact_persons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
