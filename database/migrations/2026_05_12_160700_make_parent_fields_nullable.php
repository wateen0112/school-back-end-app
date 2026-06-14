<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeParentFieldsNullable extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE my__parents 
            MODIFY National_ID_Father VARCHAR(255) NULL,
            MODIFY Passport_ID_Father VARCHAR(255) NULL,
            MODIFY Phone_Father VARCHAR(255) NULL,
            MODIFY Job_Father VARCHAR(255) NULL,
            MODIFY Nationality_Father_id BIGINT UNSIGNED NULL,
            MODIFY Blood_Type_Father_id BIGINT UNSIGNED NULL,
            MODIFY Religion_Father_id BIGINT UNSIGNED NULL,
            MODIFY Address_Father VARCHAR(255) NULL,
            MODIFY Name_Mother VARCHAR(255) NULL,
            MODIFY National_ID_Mother VARCHAR(255) NULL,
            MODIFY Passport_ID_Mother VARCHAR(255) NULL,
            MODIFY Phone_Mother VARCHAR(255) NULL,
            MODIFY Job_Mother VARCHAR(255) NULL,
            MODIFY Nationality_Mother_id BIGINT UNSIGNED NULL,
            MODIFY Blood_Type_Mother_id BIGINT UNSIGNED NULL,
            MODIFY Religion_Mother_id BIGINT UNSIGNED NULL,
            MODIFY Address_Mother VARCHAR(255) NULL
        ");
    }

    public function down()
    {
        DB::statement("ALTER TABLE my__parents 
            MODIFY National_ID_Father VARCHAR(255) NOT NULL,
            MODIFY Passport_ID_Father VARCHAR(255) NOT NULL,
            MODIFY Phone_Father VARCHAR(255) NOT NULL,
            MODIFY Job_Father VARCHAR(255) NOT NULL,
            MODIFY Nationality_Father_id BIGINT UNSIGNED NOT NULL,
            MODIFY Blood_Type_Father_id BIGINT UNSIGNED NOT NULL,
            MODIFY Religion_Father_id BIGINT UNSIGNED NOT NULL,
            MODIFY Address_Father VARCHAR(255) NOT NULL,
            MODIFY Name_Mother VARCHAR(255) NOT NULL,
            MODIFY National_ID_Mother VARCHAR(255) NOT NULL,
            MODIFY Passport_ID_Mother VARCHAR(255) NOT NULL,
            MODIFY Phone_Mother VARCHAR(255) NOT NULL,
            MODIFY Job_Mother VARCHAR(255) NOT NULL,
            MODIFY Nationality_Mother_id BIGINT UNSIGNED NOT NULL,
            MODIFY Blood_Type_Mother_id BIGINT UNSIGNED NOT NULL,
            MODIFY Religion_Mother_id BIGINT UNSIGNED NOT NULL,
            MODIFY Address_Mother VARCHAR(255) NOT NULL
        ");
    }
}
