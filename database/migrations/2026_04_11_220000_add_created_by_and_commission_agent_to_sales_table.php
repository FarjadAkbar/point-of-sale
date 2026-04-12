<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->foreignId('sales_commission_agent_id')->nullable()->after('created_by')->constrained('sales_commission_agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_commission_agent_id');
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
