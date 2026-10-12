<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillNotificationTimestamps extends Migration
{
    /**
     * Backfill NULL created_at/updated_at on notifications and
     * notification_recipients so the dashboard never shows an
     * invalid/empty date.
     */
    public function up()
    {
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->whereNull('created_at')
                ->update(['created_at' => DB::raw('COALESCE(updated_at, NOW())')]);

            DB::table('notifications')
                ->whereNull('updated_at')
                ->update(['updated_at' => DB::raw('COALESCE(created_at, NOW())')]);

            DB::table('notifications')
                ->whereNull('created_at')
                ->orWhereNull('updated_at')
                ->update(['created_at' => DB::raw('NOW()'), 'updated_at' => DB::raw('NOW()')]);
        }

        if (Schema::hasTable('notification_recipients')) {
            DB::table('notification_recipients')
                ->whereNull('created_at')
                ->update(['created_at' => DB::raw('COALESCE(updated_at, NOW())')]);

            DB::table('notification_recipients')
                ->whereNull('updated_at')
                ->update(['updated_at' => DB::raw('COALESCE(created_at, NOW())')]);

            DB::table('notification_recipients')
                ->whereNull('created_at')
                ->orWhereNull('updated_at')
                ->update(['created_at' => DB::raw('NOW()'), 'updated_at' => DB::raw('NOW()')]);
        }
    }

    public function down()
    {
        //
    }
}
