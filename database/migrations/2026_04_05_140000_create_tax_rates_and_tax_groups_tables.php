<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('amount', 8, 4);
            $table->boolean('for_tax_group')->default(false);
            $table->timestamps();

            $table->index(['team_id', 'name']);
        });

        Schema::create('tax_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['team_id', 'name']);
        });

        Schema::create('tax_group_tax_rate', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_rate_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['tax_group_id', 'tax_rate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_group_tax_rate');
        Schema::dropIfExists('tax_groups');
        Schema::dropIfExists('tax_rates');
    }
};
