<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('sales', 'restaurant_table_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropConstrainedForeignId('restaurant_table_id');
            });
        }

        if (Schema::hasColumn('products', 'preparation_time_minutes')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('preparation_time_minutes');
            });
        }

        if (Schema::hasColumn('pos_roles', 'is_service_staff')) {
            Schema::table('pos_roles', function (Blueprint $table) {
                $table->dropColumn('is_service_staff');
            });
        }

        Schema::dropIfExists('bookings');
        Schema::dropIfExists('modifier_set_items');
        Schema::dropIfExists('modifier_sets');
        Schema::dropIfExists('restaurant_tables');
    }

    public function down(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('business_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('restaurant_table_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('correspondent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_staff_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->unsignedSmallInteger('guest_count')->default(1);
            $table->dateTime('booking_start');
            $table->dateTime('booking_end')->nullable();
            $table->string('status', 20)->default('waiting');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('modifier_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('modifier_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modifier_set_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 15, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('restaurant_table_id')->nullable()->after('selling_price_group_id')->constrained()->nullOnDelete();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->unsignedSmallInteger('preparation_time_minutes')->nullable()->after('weight');
        });

        Schema::table('pos_roles', function (Blueprint $table) {
            $table->boolean('is_service_staff')->default(false)->after('name');
        });
    }
};
