<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add priority to platform announcements.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->string('priority', 20)->default('low')->after('audience');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};