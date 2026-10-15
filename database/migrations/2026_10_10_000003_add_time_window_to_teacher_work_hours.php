<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Persist the from/to window of teacher work-hour entries as real columns so
 * payroll input can reject overlapping records for the same teacher/day.
 * Existing windows (kept in notes as "08:00 → 13:30") are backfilled.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teacher_work_hours', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('work_date');
            $table->time('end_time')->nullable()->after('start_time');
        });

        DB::table('teacher_work_hours')->whereNull('start_time')->orderBy('id')->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                if (! preg_match('/^(\d{2}:\d{2}) → (\d{2}:\d{2})/', (string) ($row->notes ?? ''), $m)) {
                    continue;
                }
                DB::table('teacher_work_hours')->where('id', $row->id)->update([
                    'start_time' => $m[1].':00',
                    'end_time' => $m[2].':00',
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('teacher_work_hours', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
