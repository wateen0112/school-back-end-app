<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->index('school_id_index');
            $table->string('direction', 30)->nullable()->default('bidirectional')->after('config');
            $table->string('webhook_token', 64)->nullable()->unique()->after('direction');
            $table->string('hmac_secret', 128)->nullable()->after('webhook_token');
            $table->string('last_status', 30)->nullable()->after('hmac_secret');
            $table->timestamp('last_sync_at')->nullable()->after('last_status');
        });
    }

    public function down(): void
    {
        Schema::table('integrations', function (Blueprint $table) {
            $table->dropColumn(['school_id', 'direction', 'webhook_token', 'hmac_secret', 'last_status', 'last_sync_at']);
        });
    }
};
