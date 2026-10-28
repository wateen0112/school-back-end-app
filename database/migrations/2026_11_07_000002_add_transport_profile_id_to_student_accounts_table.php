<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('student_accounts', 'transport_profile_id')) {
                $table->foreignId('transport_profile_id')->nullable()
                    ->after('student_installment_id')
                    ->references('id')->on('transportation_student_profiles')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('student_accounts', 'transport_profile_id')) {
                $table->dropForeign(['transport_profile_id']);
                $table->dropColumn('transport_profile_id');
            }
        });
    }
};
