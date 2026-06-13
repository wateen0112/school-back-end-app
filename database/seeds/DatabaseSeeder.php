<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->flushParentAndStudentRelatedRows();

        $this->call(UserSeeder::class);
        $this->call(GradeSeeder::class);
        $this->call(ClassroomTableSeeder::class);
        $this->call(SectionsTableSeeder::class);
        $this->call(BloodTableSeeder::class);
        $this->call(NationalitiesTableSeeder::class);
        $this->call(religionTableSeeder::class);
        $this->call(SpecializationsTableSeeder::class);
        $this->call(GenderTableSeeder::class);
        $this->call(TeachersTableSeeder::class);
        $this->call(ParentsTableSeeder::class);
        $this->call(StudentsTableSeeder::class);
        $this->call(SettingsTableSeeder::class);
    }

    /**
     * Drop FK-linked rows so reference seeders can empty type__bloods, nationalities, religions, etc.
     * on a non-fresh database (e.g. second run of db:seed).
     */
    protected function flushParentAndStudentRelatedRows(): void
    {
        if (! Schema::hasTable('my__parents')) {
            return;
        }

        if (Schema::hasTable('parent_attachments')) {
            DB::table('parent_attachments')->delete();
        }

        if (Schema::hasTable('students')) {
            DB::table('students')->delete();
        }

        DB::table('my__parents')->delete();
    }
}

