<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('account_types')->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->foreignId('account_type_id')->nullable()->after('team_id')->constrained('account_types')->nullOnDelete();
            $table->decimal('opening_balance', 15, 4)->default(0)->after('notes');
            $table->json('account_details')->nullable()->after('opening_balance');
            $table->foreignId('created_by')->nullable()->after('account_details')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table) {
            $table->dropForeign(['account_type_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['account_type_id', 'opening_balance', 'account_details', 'created_by']);
        });

        Schema::dropIfExists('account_types');
    }
};
