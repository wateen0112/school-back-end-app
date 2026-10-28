<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGradePassFailRulesTable extends Migration
{
    /**
     * إعدادات النجاح والرسوب لكل صف على حدة:
     * - single_subject: مادة معينة تُعتبر مرسبة إن لم يستوفِ الطالب حدها الأدنى.
     * - combined_subjects: مادتان معينتان تُحتسبان كمجموع، ويرسب الطالب إذا
     *   لم يحقق النسبة المئوية الدنيا من مجموع علامتيهما.
     */
    public function up()
    {
        if (!Schema::hasTable('grade_pass_fail_rules')) {
            Schema::create('grade_pass_fail_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('grade_id')->constrained('Grades')->onDelete('cascade');
                $table->enum('rule_type', ['single_subject', 'combined_subjects']);

                // single_subject: المادة المحددة
                // combined_subjects: المادتان المعنيتان بالمجموع
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
                $table->foreignId('second_subject_id')->nullable()->constrained('subjects')->onDelete('cascade');

                // الحد الأدنى لعلامة المادة الواحدة (مادة مرسبة)
                $table->decimal('subject_min_mark', 8, 2)->nullable();

                // النسبة المئوية الدنيا لمجموع المادتين معاً (من مجموع العلامة العظمى لهما)
                $table->decimal('combined_min_percentage', 5, 2)->nullable();

                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['grade_id', 'rule_type']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('grade_pass_fail_rules');
    }
}
