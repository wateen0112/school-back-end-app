<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * General registry number (رقم السجل العام) for each student —
     * used on official transcripts / bulletins.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('general_record_number')->nullable()->after('academic_year');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('general_record_number');
        });
    }
};
