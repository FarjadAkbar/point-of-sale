<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_register_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_location_id')->constrained('business_locations')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 16);
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->decimal('total_card_slips', 15, 4)->default(0);
            $table->decimal('total_cheque', 15, 4)->default(0);
            $table->decimal('total_cash', 15, 4)->default(0);
            $table->decimal('total_bank_transfer', 15, 4)->default(0);
            $table->decimal('total_advance_payment', 15, 4)->default(0);
            for ($i = 1; $i <= 7; $i++) {
                $table->decimal('custom_pay_'.$i, 15, 4)->default(0);
            }
            $table->decimal('other_payments', 15, 4)->default(0);
            $table->decimal('total', 15, 4)->default(0);
            $table->timestamps();

            $table->index(['team_id', 'opened_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_register_sessions');
    }
};
