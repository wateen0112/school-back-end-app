<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCapacityToClassroomsTable extends Migration
{
    public function up()
    {
        Schema::table('Classrooms', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->after('Grade_id');
        });
    }

    public function down()
    {
        Schema::table('Classrooms', function (Blueprint $table) {
            $table->dropColumn('capacity');
        });
    }
}