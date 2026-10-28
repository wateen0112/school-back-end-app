<?php

use App\Models\Student;
use App\Models\Transportation\Bus;
use App\Models\Transportation\DriverProfile;
use App\Models\Transportation\RouteStop;
use App\Models\Transportation\StudentTransportProfile;
use App\Models\Transportation\TransportationRoute;
use App\Models\Transportation\TransportationSetting;
use App\Models\Transportation\TransportationTrip;
use App\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for the transportation module.
 *
 * Usage: php artisan db:seed --class="\TransportationDemoSeeder"
 *
 * Creates for the first school:
 *  - 4 buses (BUS-01..BUS-04) with plates and capacities
 *  - 3 driver accounts (driver.demo / driver2.demo / driver3.demo @school.local,
 *    password driver123) with driver role, profiles and assigned buses
 *  - 3 lines (A/B/C), each with a morning + afternoon route
 *  - transportation profiles for up to 4 students (lines A & B)
 *  - scheduled morning trips for today on lines A & B
 * Idempotent — safe to re-run.
 */
class TransportationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = \App\Models\School::first();
        if (! $school) {
            $this->command?->warn('No school found — create a school first (run UserSeeder).');
            return;
        }

        TransportationSetting::forSchool($school->id);

        // ------------------------------------------------------- buses
        $buses = collect([
            ['bus_number' => 'BUS-01', 'name' => 'Mercedes Sprinter', 'license_plate' => 'KSA-1010', 'capacity' => 30, 'status' => 'AVAILABLE'],
            ['bus_number' => 'BUS-02', 'name' => 'Toyota Coaster', 'license_plate' => 'KSA-2020', 'capacity' => 25, 'status' => 'AVAILABLE'],
            ['bus_number' => 'BUS-03', 'name' => 'Hyundai County', 'license_plate' => 'KSA-3030', 'capacity' => 40, 'status' => 'AVAILABLE'],
            ['bus_number' => 'BUS-04', 'name' => 'Ford Transit', 'license_plate' => 'KSA-4040', 'capacity' => 20, 'status' => 'MAINTENANCE'],
        ])->map(fn ($b) => Bus::firstOrCreate(
            ['school_id' => $school->id, 'bus_number' => $b['bus_number']],
            [
                'name' => $b['name'],
                'license_plate' => $b['license_plate'],
                'capacity' => $b['capacity'],
                'status' => $b['status'],
            ]
        ));

        // ------------------------------------------------------- drivers
        $driverRole = $this->ensureDriverRole($school);

        $drivers = collect([
            ['email' => 'driver.demo@school.local', 'name' => 'Driver One', 'phone' => '0500000001', 'license_no' => 'DL-1001', 'bus' => $buses[0]],
            ['email' => 'driver2.demo@school.local', 'name' => 'Driver Two', 'phone' => '0500000002', 'license_no' => 'DL-1002', 'bus' => $buses[1]],
            ['email' => 'driver3.demo@school.local', 'name' => 'Driver Three', 'phone' => '0500000003', 'license_no' => 'DL-1003', 'bus' => $buses[2]],
        ])->map(function ($d) use ($school, $driverRole) {
            $user = User::firstOrCreate(
                ['email' => $d['email']],
                [
                    'name' => $d['name'],
                    'password' => Hash::make('driver123'),
                    'school_id' => $school->id,
                ]
            );
            $user->roles()->syncWithoutDetaching([$driverRole->id]);

            DriverProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $school->id,
                    'phone' => $d['phone'],
                    'license_no' => $d['license_no'],
                    'assigned_bus_id' => $d['bus']->id,
                ]
            );

            return (object) ['user' => $user, 'bus' => $d['bus']];
        });

        // ------------------------------------------------------- students
        $students = $this->ensureStudents($school);

        // ------------------------------------------------------- lines (routes)
        // Demo coordinates spread around Riyadh center.
        $points = [
            [24.7250, 46.6800], [24.7180, 46.6600],
            [24.7000, 46.6850], [24.6920, 46.6600],
        ];

        $lineDefs = [
            ['label' => 'A', 'code' => 'A', 'driver' => $drivers[0], 'start' => '07:00', 'end' => '14:30'],
            ['label' => 'B', 'code' => 'B', 'driver' => $drivers[1], 'start' => '07:15', 'end' => '14:45'],
            ['label' => 'C', 'code' => 'C', 'driver' => $drivers[2], 'start' => '07:30', 'end' => '15:00'],
        ];

        foreach ($lineDefs as $line) {
            $morningRoute = TransportationRoute::firstOrCreate(
                ['school_id' => $school->id, 'name' => 'Line ' . $line['label'] . ' — Morning'],
                [
                    'code' => 'AM-' . $line['code'],
                    'direction' => 'HOME_TO_SCHOOL',
                    'bus_id' => $line['driver']->bus->id,
                    'driver_user_id' => $line['driver']->user->id,
                    'scheduled_start_time' => $line['start'],
                    'is_active' => true,
                ]
            );
            $afternoonRoute = TransportationRoute::firstOrCreate(
                ['school_id' => $school->id, 'name' => 'Line ' . $line['label'] . ' — Afternoon'],
                [
                    'code' => 'PM-' . $line['code'],
                    'direction' => 'SCHOOL_TO_HOME',
                    'bus_id' => $line['driver']->bus->id,
                    'driver_user_id' => $line['driver']->user->id,
                    'scheduled_start_time' => $line['end'],
                    'is_active' => true,
                ]
            );

            // Assign the first 4 students to lines A & B only.
            if (in_array($line['code'], ['A', 'B'])) {
                foreach ($students as $index => $student) {
                    [$lat, $lng] = $points[$index % count($points)];

                    StudentTransportProfile::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'school_id' => $school->id,
                            'is_enabled' => true,
                            'agreed_amount' => 400 + $index * 50,
                            'academic_year_id' => $student->academic_year_id,
                            'classroom_id' => $student->Classroom_id,
                            'morning_route_id' => $morningRoute->id,
                            'afternoon_route_id' => $afternoonRoute->id,
                            'morning_pickup_address' => 'Student home #' . ($index + 1),
                            'morning_pickup_lat' => $lat,
                            'morning_pickup_lng' => $lng,
                            'morning_pickup_time' => '06:5' . $index,
                            'afternoon_dropoff_address' => 'Student home #' . ($index + 1),
                            'afternoon_dropoff_lat' => $lat,
                            'afternoon_dropoff_lng' => $lng,
                        ]
                    );

                    RouteStop::firstOrCreate(
                        ['route_id' => $morningRoute->id, 'student_id' => $student->id],
                        ['stop_order' => $index + 1]
                    );
                    RouteStop::firstOrCreate(
                        ['route_id' => $afternoonRoute->id, 'student_id' => $student->id],
                        ['stop_order' => $students->count() - $index]
                    );
                }
            }

            // Scheduled morning trip for today on every active line.
            TransportationTrip::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'route_id' => $morningRoute->id,
                    'trip_date' => today()->toDateString(),
                ],
                [
                    'bus_id' => $line['driver']->bus->id,
                    'driver_user_id' => $line['driver']->user->id,
                    'direction' => 'HOME_TO_SCHOOL',
                    'scheduled_start_time' => $line['start'],
                    'status' => 'SCHEDULED',
                ]
            );
        }

        $this->command?->info('Transportation demo seeded.');
        $this->command?->info('Drivers (password driver123): driver.demo / driver2.demo / driver3.demo @school.local');
    }

    /**
     * The role name column stores translatable JSON — search both forms so
     * repeated seeding never creates duplicates.
     */
    private function ensureDriverRole($school)
    {
        return \App\Models\Role::where('school_id', $school->id)
            ->where(function ($q) {
                $q->where('name', 'driver')->orWhere('name->en', 'driver');
            })
            ->first() ?? \App\Models\Role::create([
                'name' => ['en' => 'Driver', 'ar' => 'سائق'],
                'description' => 'School bus driver — mobile app access to assigned trips only',
                'permissions' => [],
                'is_active' => true,
                'school_id' => $school->id,
            ]);
    }

    /**
     * Guarantee at least 4 students exist for the school: adopt orphaned
     * demo students (school_id NULL) and create additional linked students
     * until the roster has 4.
     *
     * @return \Illuminate\Support\Collection<int, Student>
     */
    private function ensureStudents($school)
    {
        Student::whereNull('school_id')
            ->orWhere('school_id', $school->id)
            ->limit(4)
            ->get()
            ->each(function ($student) use ($school) {
                if ($student->school_id === null) {
                    $student->school_id = $school->id;
                    $student->save();
                }
            });

        $students = Student::where('school_id', $school->id)->take(4)->get();

        $demoNames = [
            ['en' => 'Lina Ahmed', 'ar' => 'لينا أحمد'],
            ['en' => 'Youssef Khalid', 'ar' => 'يوسف خالد'],
            ['en' => 'Sara Nasser', 'ar' => 'سارة ناصر'],
            ['en' => 'Adam Fahad', 'ar' => 'آدم فهد'],
        ];
        $index = 0;
        while ($students->count() < 4 && $index < count($demoNames)) {
            $section = \App\Models\Section::with('My_classs', 'Grades')->first();
            $parent = \App\Models\My_Parent::first();
            if (! $section || ! $parent) {
                $this->command?->warn('Cannot create students: missing Section/Parent demo data.');
                break;
            }

            // updateOrCreate: after a school wipe these emails can survive
            // as orphans, so a plain create would hit the unique key.
            $student = Student::updateOrCreate(
                ['email' => 'student.transport.' . ($index + 1) . '@school.local'],
                [
                'name' => $demoNames[$index],
                'password' => Hash::make('password123'),
                'gender_id' => \App\Models\Gender::first()?->id ?? 1,
                'nationalitie_id' => \App\Models\Nationalitie::first()?->id ?? 1,
                'blood_id' => \App\Models\Type_Blood::first()?->id ?? 1,
                'Date_Birth' => '2013-06-01',
                'Grade_id' => $section->Grades->id,
                'Classroom_id' => $section->My_classs->id,
                'section_id' => $section->id,
                'parent_id' => $parent->id,
                'academic_year' => date('Y') . '-' . (date('Y') + 1),
                'school_id' => $school->id,
                ]
            );
            $students->push($student);
            $index++;
        }

        return $students;
    }
}
