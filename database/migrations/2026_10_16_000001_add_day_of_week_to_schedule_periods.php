<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-day lesson times: a schedule period may now be scoped to a specific
 * weekday (0=Sunday..6=Saturday). NULL day_of_week = applies to every day
 * (the default/backward-compatible behaviour).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule_periods', function (Blueprint $table) {
            $table->unsignedTinyInteger('day_of_week')->nullable()->after('period_number'); // 0..6, NULL = all days
        });

        Schema::table('schedule_periods', function (Blueprint $table) {
            // Plain index on the FK column first: MySQL refuses to drop the
            // old unique index while it backs the academic_years foreign key.
            $table->index('academic_year_id', 'schedule_period_year_index');
            $table->dropUnique(['academic_year_id', 'period_number']);
        });

        Schema::table('schedule_periods', function (Blueprint $table) {
            $table->unique(['academic_year_id', 'day_of_week', 'period_number'], 'schedule_period_day_unique');
            $table->index(['academic_year_id', 'day_of_week'], 'schedule_period_day_index');
        });
    }

    public function down(): void
    {
        Schema::table('schedule_periods', function (Blueprint $table) {
            $table->dropUnique('schedule_period_day_unique');
            $table->dropIndex('schedule_period_day_index');
            $table->dropColumn('day_of_week');
            $table->unique(['academic_year_id', 'period_number']);
        });
    }
};
