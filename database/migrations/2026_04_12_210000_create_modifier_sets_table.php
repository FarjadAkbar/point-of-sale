<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modifier_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });

        Schema::create('modifier_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_set_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['modifier_set_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modifier_set_items');
        Schema::dropIfExists('modifier_sets');
    }
};
