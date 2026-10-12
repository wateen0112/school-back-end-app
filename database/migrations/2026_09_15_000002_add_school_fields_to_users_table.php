<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Attach admins to a school and flag the super admin account.
     *
     * The super admin lives outside any school (school_id = null). School
     * admins are plain users with a school_id. Existing demo accounts are
     * backfilled: superadmin@school.local becomes the super admin, and a
     * "Demo School" row is created for admin@school.local.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('school_id')->nullable()->after('password');
            $table->boolean('is_super_admin')->default(false)->after('school_id');
            $table->foreign('school_id')->references('id')->on('schools')->onDelete('set null');
        });

        DB::table('users')->where('email', 'superadmin@school.local')->update(['is_super_admin' => true]);

        if (! DB::table('schools')->exists() && DB::table('users')->where('email', 'admin@school.local')->exists()) {
            $schoolId = DB::table('schools')->insertGetId([
                'name' => json_encode(['en' => 'Demo School', 'ar' => 'المدرسة التجريبية']),
                'email' => 'admin@school.local',
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('users')->where('email', 'admin@school.local')->update(['school_id' => $schoolId]);
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropColumn(['school_id', 'is_super_admin']);
        });
    }
};