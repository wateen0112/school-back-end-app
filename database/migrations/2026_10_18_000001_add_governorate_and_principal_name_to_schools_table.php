<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add governorate and principal name to the school (used by the official
     * transcript / printing headers).
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('governorate')->nullable()->after('address');
            $table->string('principal_name')->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['governorate', 'principal_name']);
        });
    }
};
