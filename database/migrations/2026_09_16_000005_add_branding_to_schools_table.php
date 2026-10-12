<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-school branding: a custom logo uploaded by the super admin and a
     * custom color theme consumed by the school dashboard.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('status');
            $table->json('theme')->nullable()->after('logo');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn(['logo', 'theme']);
        });
    }
};