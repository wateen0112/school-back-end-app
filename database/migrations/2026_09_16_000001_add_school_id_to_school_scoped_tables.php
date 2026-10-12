<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $party = ['students', 'teachers', 'my__parents'];
        foreach ($party as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('school_id')->nullable()->index('school_id_index');
            });
        }

        // Backfill demo data to school id 1 (single-tenant data was seeded without it)
        $schoolId = DB::table('schools')->first()?->id ?? 1;
        foreach ($party as $table) {
            DB::table($table)->whereNull('school_id')->update(['school_id' => $schoolId]);
        }
    }

    public function down(): void
    {
        foreach (['students', 'teachers', 'my__parents'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex('school_id_index');
                $t->dropColumn('school_id');
            });
        }
    }
};
