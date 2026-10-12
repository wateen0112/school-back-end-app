<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeStudentsParentIdNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Doctrine DBAL is not available, so use raw SQL for MySQL/MariaDB.
        if (Schema::hasTable('students')) {
            // Get the actual foreign key name for students_parent_id_foreign.
            $foreignKey = $this->getForeignKeyName('students', 'parent_id');
            if ($foreignKey) {
                DB::statement("ALTER TABLE `students` DROP FOREIGN KEY `{$foreignKey}`");
            }
            DB::statement('ALTER TABLE `students` MODIFY `parent_id` BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE `students` ADD CONSTRAINT `students_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `my__parents` (`id`) ON DELETE SET NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('students')) {
            $foreignKey = $this->getForeignKeyName('students', 'parent_id');
            if ($foreignKey) {
                DB::statement("ALTER TABLE `students` DROP FOREIGN KEY `{$foreignKey}`");
            }
            DB::statement('ALTER TABLE `students` MODIFY `parent_id` BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE `students` ADD CONSTRAINT `students_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `my__parents` (`id`) ON DELETE CASCADE');
        }
    }

    /**
     * Find the actual foreign key name for a column.
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $results = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL", [$table, $column]);
        return $results[0]->CONSTRAINT_NAME ?? null;
    }
}
