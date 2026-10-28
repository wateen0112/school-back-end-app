<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transportation_student_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('transportation_student_profiles', 'agreed_amount')) {
                $table->decimal('agreed_amount', 10, 2)->nullable()->after('afternoon_route_id');
            }

            if (! Schema::hasColumn('transportation_student_profiles', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()
                    ->after('agreed_amount')
                    ->references('id')->on('academic_years')->nullOnDelete();
            }

            if (! Schema::hasColumn('transportation_student_profiles', 'classroom_id')) {
                $table->foreignId('classroom_id')->nullable()
                    ->after('academic_year_id')
                    ->references('id')->on('Classrooms')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transportation_student_profiles', function (Blueprint $table) {
            if (Schema::hasColumn('transportation_student_profiles', 'classroom_id')) {
                $table->dropForeign(['classroom_id']);
                $table->dropColumn('classroom_id');
            }

            if (Schema::hasColumn('transportation_student_profiles', 'academic_year_id')) {
                $table->dropForeign(['academic_year_id']);
                $table->dropColumn('academic_year_id');
            }

            if (Schema::hasColumn('transportation_student_profiles', 'agreed_amount')) {
                $table->dropColumn('agreed_amount');
            }
        });
    }
};
