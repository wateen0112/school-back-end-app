<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (! Schema::hasColumn('students', 'home_lat')) {
                $table->decimal('home_lat', 10, 7)->nullable()->after('residence_address');
            }
            if (! Schema::hasColumn('students', 'home_lng')) {
                $table->decimal('home_lng', 10, 7)->nullable()->after('home_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'home_lng')) {
                $table->dropColumn('home_lng');
            }
            if (Schema::hasColumn('students', 'home_lat')) {
                $table->dropColumn('home_lat');
            }
        });
    }
};
