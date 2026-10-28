<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * إعادة صياغة قاعدة "مادتان كمجموع":
 * لا يُشترط تحديد مادتين محددتين — القاعدة تُطبق على أي مادتين من مواد
 * الطالب، مع قائمة استثناء (excluded_subject_ids) تُستبعد موادها من القاعدة.
 */
class AddExcludedSubjectsToPassFailRules extends Migration
{
    public function up()
    {
        Schema::table('grade_pass_fail_rules', function (Blueprint $table) {
            // المواد المستثناة من قاعدة المجموع (لا تُحتسب ضمن أي زوج)
            $table->json('excluded_subject_ids')->nullable()->after('combined_min_percentage');
        });
    }

    public function down()
    {
        Schema::table('grade_pass_fail_rules', function (Blueprint $table) {
            $table->dropColumn('excluded_subject_ids');
        });
    }
}
