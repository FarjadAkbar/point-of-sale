<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variation_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });

        Schema::create('variation_template_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('variation_template_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['variation_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variation_template_values');
        Schema::dropIfExists('variation_templates');
    }
};
