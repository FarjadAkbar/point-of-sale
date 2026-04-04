<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_commission_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('prefix')->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('contact_no')->nullable();
            $table->text('address')->nullable();
            $table->decimal('cmmsn_percent', 8, 4);
            $table->timestamps();

            $table->index(['team_id', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_commission_agents');
    }
};
