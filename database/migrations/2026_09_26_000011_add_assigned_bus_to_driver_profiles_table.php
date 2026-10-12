<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportation_driver_profiles', function (Blueprint $table) {
            $table->foreignId('assigned_bus_id')->nullable()->after('license_no')
                ->references('id')->on('transportation_buses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transportation_driver_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_bus_id');
        });
    }
};
