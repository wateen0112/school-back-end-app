<?php

use App\Models\School;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Demo admin users for guard "web" (/login/admin).
     *
     * All demo logins use password: password123
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();
        DB::table('schools')->delete();

        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@school.local',
            'password' => Hash::make('password123'),
            'is_super_admin' => true,
        ]);

        $demoSchool = School::create([
            'name' => ['en' => 'Demo School', 'ar' => 'المدرسة التجريبية'],
            'email' => 'admin@school.local',
            'status' => 'active',
        ]);

        User::create([
            'name' => 'School Admin',
            'email' => 'admin@school.local',
            'password' => Hash::make('password123'),
            'school_id' => $demoSchool->id,
            'is_super_admin' => false,
            'salary' => 3000.00,
        ]);
    }
}
