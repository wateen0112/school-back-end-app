<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->json('old_values')->nullable()->after('meta');
            $table->json('new_values')->nullable()->after('old_values');
            $table->string('reason')->nullable()->after('new_values');
            $table->string('approval_reference')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn(['old_values', 'new_values', 'reason', 'approval_reference']);
        });
    }
};
