<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonnelFieldsToStudentsTeachersUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // الطلاب: اسم الجد، الرقم الوطني (رقم البطاقة)، مكان الإقامة (اختياري)
        Schema::table('students', function (Blueprint $table) {
            $table->string('grandfather_name')->nullable()->after('name');
            $table->string('national_id', 50)->nullable()->after('grandfather_name');
            $table->string('residence_address')->nullable()->after('national_id');
        });

        // المعلمون: الكنية، رقم الموبايل، رقم التأمين (اختياري)
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('Name');
            $table->string('mobile', 30)->nullable()->after('surname');
            $table->string('insurance_number', 100)->nullable()->after('mobile');
        });

        // الموظفون والمستخدمون: رقم التأمين (اختياري)
        Schema::table('users', function (Blueprint $table) {
            $table->string('insurance_number', 100)->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['grandfather_name', 'national_id', 'residence_address']);
        });

        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn(['surname', 'mobile', 'insurance_number']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('insurance_number');
        });
    }
}
