<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location_id')->nullable();
            $table->string('landmark')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('mobile')->nullable();
            $table->string('alternate_contact_number')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->foreignId('default_selling_price_group_id')
                ->nullable()
                ->constrained('selling_price_groups')
                ->nullOnDelete();
            $table->json('featured_product_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_locations');
    }
};
