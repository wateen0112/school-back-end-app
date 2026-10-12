<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * School lifecycle columns: suspension, archival and soft delete.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->timestamp('suspended_at')->nullable()->after('status');
            $table->timestamp('archived_at')->nullable()->after('suspended_at');
            $table->softDeletes()->after('archived_at');
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['suspended_at', 'archived_at']);
        });
    }
};