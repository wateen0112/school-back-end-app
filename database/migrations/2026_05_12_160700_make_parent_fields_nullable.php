<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeParentFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('my__parents', function (Blueprint $table) {
            $table->string('National_ID_Father')->nullable()->change();
            $table->string('Passport_ID_Father')->nullable()->change();
            $table->string('Phone_Father')->nullable()->change();
            $table->string('Job_Father')->nullable()->change();
            $table->bigInteger('Nationality_Father_id')->nullable()->change();
            $table->bigInteger('Blood_Type_Father_id')->nullable()->change();
            $table->bigInteger('Religion_Father_id')->nullable()->change();
            $table->string('Address_Father')->nullable()->change();
            $table->string('Name_Mother')->nullable()->change();
            $table->string('National_ID_Mother')->nullable()->change();
            $table->string('Passport_ID_Mother')->nullable()->change();
            $table->string('Phone_Mother')->nullable()->change();
            $table->string('Job_Mother')->nullable()->change();
            $table->bigInteger('Nationality_Mother_id')->nullable()->change();
            $table->bigInteger('Blood_Type_Mother_id')->nullable()->change();
            $table->bigInteger('Religion_Mother_id')->nullable()->change();
            $table->string('Address_Mother')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('my__parents', function (Blueprint $table) {
            $table->string('National_ID_Father')->nullable(false)->change();
            $table->string('Passport_ID_Father')->nullable(false)->change();
            $table->string('Phone_Father')->nullable(false)->change();
            $table->string('Job_Father')->nullable(false)->change();
            $table->bigInteger('Nationality_Father_id')->nullable(false)->change();
            $table->bigInteger('Blood_Type_Father_id')->nullable(false)->change();
            $table->bigInteger('Religion_Father_id')->nullable(false)->change();
            $table->string('Address_Father')->nullable(false)->change();
            $table->string('Name_Mother')->nullable(false)->change();
            $table->string('National_ID_Mother')->nullable(false)->change();
            $table->string('Passport_ID_Mother')->nullable(false)->change();
            $table->string('Phone_Mother')->nullable(false)->change();
            $table->string('Job_Mother')->nullable(false)->change();
            $table->bigInteger('Nationality_Mother_id')->nullable(false)->change();
            $table->bigInteger('Blood_Type_Mother_id')->nullable(false)->change();
            $table->bigInteger('Religion_Mother_id')->nullable(false)->change();
            $table->string('Address_Mother')->nullable(false)->change();
        });
    }
}
