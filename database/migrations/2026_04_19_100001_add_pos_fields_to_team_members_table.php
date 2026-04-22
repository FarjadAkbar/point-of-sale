<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->foreignId('pos_role_id')->nullable()->after('role')->constrained('pos_roles')->nullOnDelete();
            $table->json('settings')->nullable()->after('pos_role_id');
        });
    }

    public function down(): void
    {
        Schema::table('team_members', function (Blueprint $table) {
            $table->dropForeign(['pos_role_id']);
            $table->dropColumn(['pos_role_id', 'settings']);
        });
    }
};
