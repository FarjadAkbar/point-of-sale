<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->index(['team_id', 'created_by']);
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->index(['team_id', 'created_by']);
        });

        Schema::table('warranties', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('team_id')->constrained('users')->nullOnDelete();
            $table->index(['team_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('units', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('warranties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
