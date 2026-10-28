<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Gender;
use App\Models\Grade;
use App\Models\My_Parent;
use App\Models\Nationalitie;
use App\Models\Notification;
use App\Models\Religion;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentAccount;
use App\Models\AcademicYear;
use App\Models\Transportation\Bus;
use App\Models\Transportation\DriverProfile;
use App\Models\Transportation\RouteStop;
use App\Models\Transportation\StudentTransportProfile;
use App\Models\Transportation\TransportationRoute;
use App\Models\Transportation\TransportationTrip;
use App\Models\Transportation\TripEvent;
use App\Models\Transportation\TripStudent;
use App\Models\Type_Blood;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TransportationApiTest extends TestCase
{
    use RefreshDatabase;

    protected School $school;
    protected User $admin;
    protected Student $student;
    protected My_Parent $parent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::create([
            'name' => ['en' => 'Test School', 'ar' => 'مدرسة تجريبية'],
            'email' => 'school@test.local',
        ]);
        $this->admin = User::factory()->create(['school_id' => $this->school->id]);
        $this->student = $this->makeStudent($this->school->id);
        $this->parent = $this->student->parent;
    }

    // ------------------------------------------------------------ helpers

    private function makeStudent(int $schoolId): Student
    {
        $gender = Gender::create(['Name' => 'Male']);
        $nationality = Nationalitie::create(['Name' => 'Syrian']);
        $blood = Type_Blood::create(['Name' => 'O+']);
        $grade = Grade::create(['Name' => 'First Grade', 'Notes' => '']);
        $classroom = Classroom::create(['Name_Class' => '1-A', 'Grade_id' => $grade->id]);
        $parent = My_Parent::create([
            'email' => 'parent' . rand(1000, 9999) . '@test.local',
            'password' => bcrypt('password'),
            'Name_Father' => 'Father',
            'National_ID_Father' => '1',
            'Phone_Father' => '1',
            'Job_Father' => '1',
            'Passport_ID_Father' => '1',
            'Nationality_Father_id' => $nationality->id,
            'Blood_Type_Father_id' => $blood->id,
            'Religion_Father_id' => Religion::create(['Name' => 'Islam'])->id,
            'Address_Father' => 'addr',
        ]);

        return Student::create([
            'name' => ['en' => 'Test Student', 'ar' => 'طالب تجريبي'],
            'email' => 'student' . rand(1000, 9999) . '@test.local',
            'password' => bcrypt('password'),
            'gender_id' => $gender->id,
            'nationalitie_id' => $nationality->id,
            'blood_id' => $blood->id,
            'Grade_id' => $grade->id,
            'Classroom_id' => $classroom->id,
            'section_id' => Section::create([
                'Name_Section' => json_encode(['en' => 'A', 'ar' => 'أ']),
                'Grade_id' => $grade->id,
                'Class_id' => $classroom->id,
                'Status' => 1,
            ])->id,
            'parent_id' => $parent->id,
            'Date_Birth' => '2015-01-01',
            'academic_year' => '2026-2027',
            'school_id' => $schoolId,
        ]);
    }

    /** Bus + driver user/profile + route with the student as stop #1. */
    /** Academic year used by the finance-aware assignment tests. */
    private function makeAcademicYear(): AcademicYear
    {
        return AcademicYear::create([
            'school_id' => $this->school->id,
            'name' => ['en' => '2026-2027', 'ar' => '2026-2027'],
            'start_date' => '2026-09-01',
            'end_date' => '2027-06-30',
        ]);
    }

    private function makeRouteWithDriver(): array
    {
        $bus = Bus::create(['school_id' => $this->school->id, 'bus_number' => 'BUS-1', 'capacity' => 30]);

        $driverUser = User::factory()->create([
            'school_id' => $this->school->id,
            'email' => 'driver' . rand(1000, 9999) . '@test.local',
        ]);
        $driver = DriverProfile::create(['school_id' => $this->school->id, 'user_id' => $driverUser->id]);

        $route = TransportationRoute::create([
            'school_id' => $this->school->id,
            'name' => 'Route A',
            'direction' => 'HOME_TO_SCHOOL',
            'bus_id' => $bus->id,
            'driver_user_id' => $driverUser->id,
            'scheduled_start_time' => '07:00',
        ]);
        RouteStop::create(['route_id' => $route->id, 'student_id' => $this->student->id, 'stop_order' => 1]);

        StudentTransportProfile::create([
            'school_id' => $this->school->id,
            'student_id' => $this->student->id,
            'is_enabled' => true,
            'morning_route_id' => $route->id,
            'morning_pickup_lat' => 24.7136,
            'morning_pickup_lng' => 46.6753,
            'morning_pickup_address' => 'Home',
        ]);

        return [$bus, $driverUser, $driver, $route];
    }

    private function makeTrip(TransportationRoute $route, Bus $bus, User $driverUser): TransportationTrip
    {
        return TransportationTrip::create([
            'school_id' => $this->school->id,
            'route_id' => $route->id,
            'bus_id' => $bus->id,
            'driver_user_id' => $driverUser->id,
            'direction' => 'HOME_TO_SCHOOL',
            'trip_date' => today()->toDateString(),
            'scheduled_start_time' => '07:00',
            'status' => 'SCHEDULED',
        ]);
    }

    // ------------------------------------------------------------ admin

    #[Test]
    public function admin_can_create_and_list_buses()
    {
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/transportation/buses', [
            'bus_number' => 'BUS-9',
            'capacity' => 40,
        ]);
        $response->assertStatus(201)->assertJsonPath('data.bus_number', 'BUS-9');

        $this->actingAs($this->admin, 'api')->getJson('/api/transportation/buses')
            ->assertOk()
            ->assertJsonPath('data.data.0.bus_number', 'BUS-9');
    }

    #[Test]
    public function admin_cannot_see_other_schools_bus()
    {
        $otherSchool = School::create(['name' => ['en' => 'Other'], 'email' => 'o@test.local']);
        $otherBus = Bus::create(['school_id' => $otherSchool->id, 'bus_number' => 'X']);

        $this->actingAs($this->admin, 'api')
            ->getJson("/api/transportation/buses/{$otherBus->id}")
            ->assertStatus(404);
    }

    #[Test]
    public function role_without_transportation_permission_is_forbidden()
    {
        $limited = User::factory()->create(['school_id' => $this->school->id]);
        $role = Role::create([
            'name' => ['en' => 'Limited', 'ar' => 'محدود'],
            'permissions' => ['students.manage' => true],
            'is_active' => true,
            'school_id' => $this->school->id,
        ]);
        $limited->roles()->attach($role->id);

        $this->actingAs($limited, 'api')
            ->getJson('/api/transportation/buses')
            ->assertStatus(403);
    }

    #[Test]
    public function admin_can_create_driver_with_user_account_and_role()
    {
        $response = $this->actingAs($this->admin, 'api')->postJson('/api/transportation/drivers', [
            'name' => 'Driver One',
            'email' => 'd1@test.local',
            'password' => 'secret123',
            'phone' => '0500000001',
        ]);

        $response->assertStatus(201)->assertJsonPath('data.user.email', 'd1@test.local');

        $driverUser = User::where('email', 'd1@test.local')->first();
        $this->assertNotNull($driverUser);
        $this->assertDatabaseHas('role_user', ['user_id' => $driverUser->id]);
        $this->assertDatabaseHas('transportation_driver_profiles', ['user_id' => $driverUser->id]);
    }

    #[Test]
    public function admin_can_assign_student_profile_with_coordinates()
    {
        $year = $this->makeAcademicYear();

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 500,
                'academic_year_id' => $year->id,
                'morning_pickup_lat' => 24.7136,
                'morning_pickup_lng' => 46.6753,
                'morning_pickup_address' => 'Riyadh, Al Olaya',
            ]);

        $response->assertOk()->assertJsonPath('data.is_enabled', true);
        $this->assertDatabaseHas('transportation_student_profiles', [
            'student_id' => $this->student->id,
            'morning_pickup_lat' => 24.7136,
            'agreed_amount' => 500,
            'academic_year_id' => $year->id,
            // classroom defaults to the student's own enrollment
            'classroom_id' => $this->student->Classroom_id,
        ]);
    }

    #[Test]
    public function enabled_assignment_requires_agreed_amount()
    {
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
            ])
            ->assertStatus(422);
    }

    #[Test]
    public function assignment_creates_transport_accounting_entry()
    {
        $year = $this->makeAcademicYear();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 750.50,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $profile = StudentTransportProfile::where('student_id', $this->student->id)->firstOrFail();

        $this->assertDatabaseHas('student_accounts', [
            'student_id' => $this->student->id,
            'transport_profile_id' => $profile->id,
            'type' => 'transport',
            'Debit' => 750.50,
            'credit' => 0,
        ]);

        // Amount update syncs the same entry instead of duplicating it.
        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 800,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $this->assertEquals(1, StudentAccount::where('transport_profile_id', $profile->id)->count());
        $this->assertDatabaseHas('student_accounts', [
            'transport_profile_id' => $profile->id,
            'Debit' => 800,
        ]);
    }

    #[Test]
    public function disabling_profile_removes_transport_accounting_entry()
    {
        $year = $this->makeAcademicYear();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 400,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $this->assertEquals(1, StudentAccount::where('type', 'transport')->count());

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => false,
                'agreed_amount' => 400,
            ])
            ->assertOk();

        $this->assertEquals(0, StudentAccount::where('type', 'transport')->count());
    }

    #[Test]
    public function accounting_summary_reports_transport_charges()
    {
        $year = $this->makeAcademicYear();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 600,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/accounting/summary')
            ->assertOk()
            ->assertJsonPath('data.transport_charges', 600);
    }

    #[Test]
    public function transportation_finance_returns_agreed_totals()
    {
        $year = $this->makeAcademicYear();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 300,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/transportation/students/finance')
            ->assertOk()
            ->assertJsonPath('data.students_count', 1)
            ->assertJsonPath('data.total_agreed', 300)
            ->assertJsonPath('data.total_charged', 300)
            ->assertJsonPath('data.by_classroom.0.classroom_id', $this->student->Classroom_id)
            ->assertJsonPath('data.by_academic_year.0.academic_year_id', $year->id);
    }

    #[Test]
    public function index_filters_students_by_academic_year()
    {
        $year = $this->makeAcademicYear();

        $this->actingAs($this->admin, 'api')
            ->putJson("/api/transportation/students/{$this->student->id}/transport-profile", [
                'is_enabled' => true,
                'agreed_amount' => 300,
                'academic_year_id' => $year->id,
            ])
            ->assertOk();

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/transportation/students?academic_year_id=' . $year->id)
            ->assertOk()
            ->assertJsonPath('data.total', 1);

        $this->actingAs($this->admin, 'api')
            ->getJson('/api/transportation/students?academic_year_id=' . ($year->id + 999))
            ->assertOk()
            ->assertJsonPath('data.total', 0);
    }

    #[Test]
    public function admin_can_schedule_trip_and_view_history()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();

        $response = $this->actingAs($this->admin, 'api')->postJson('/api/transportation/trips', [
            'route_id' => $route->id,
            'trip_date' => today()->toDateString(),
        ]);
        $response->assertStatus(201)->assertJsonPath('data.status', 'SCHEDULED');

        // duplicate bus+date+direction clash
        $this->actingAs($this->admin, 'api')->postJson('/api/transportation/trips', [
            'route_id' => $route->id,
            'trip_date' => today()->toDateString(),
        ])->assertStatus(409);
    }

    // ------------------------------------------------------------ driver

    #[Test]
    public function driver_full_trip_lifecycle_over_http()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        $driver = fn ($method, $uri, $data = []) =>
            $this->actingAs($driverUser, 'web')->{$method . 'Json'}("/api/driver{$uri}", $data);

        // home + today
        $driver('get', '/home')->assertOk()->assertJsonPath('data.active_trip', null);
        $driver('get', '/trips/today')->assertOk()->assertJsonCount(1, 'data');

        // start
        $driver('post', "/trips/{$trip->id}/start")->assertOk()->assertJsonPath('data.status', 'IN_PROGRESS');
        $this->assertDatabaseHas('transportation_trip_students', [
            'trip_id' => $trip->id, 'student_id' => $this->student->id, 'status' => 'WAITING',
        ]);

        // location far then near (geofence)
        $driver('post', "/trips/{$trip->id}/location", ['latitude' => 24.9, 'longitude' => 46.9]);
        $driver('post', "/trips/{$trip->id}/location", ['latitude' => 24.7137, 'longitude' => 46.6754])
            ->assertOk();
        $this->assertDatabaseHas('transportation_trip_events', [
            'trip_id' => $trip->id, 'type' => 'BUS_APPROACHING_PICKUP',
        ]);

        // next stop
        $next = $driver('get', "/trips/{$trip->id}/next-stop");
        $next->assertOk()->assertJsonPath('data.next.student.id', $this->student->id);
        $tripStudentId = $next->json('data.next.trip_student_id');

        // picked up + idempotent retry
        $payload = ['latitude' => 24.7137, 'longitude' => 46.6754, 'client_event_id' => 'uuid-1'];
        $driver('post', "/trips/{$trip->id}/students/{$tripStudentId}/picked-up", $payload)->assertOk();
        $retry = $driver('post', "/trips/{$trip->id}/students/{$tripStudentId}/picked-up", $payload);
        $retry->assertOk()->assertJsonPath('data.changed', false);
        $this->assertEquals(1, TripEvent::where('trip_id', $trip->id)->where('type', 'STUDENT_PICKED_UP')->count());

        // dropped off
        $driver('post', "/trips/{$trip->id}/students/{$tripStudentId}/dropped-off",
            ['latitude' => 24.72, 'longitude' => 46.68, 'client_event_id' => 'uuid-2'])->assertOk();

        // end
        $driver('post', "/trips/{$trip->id}/end")->assertOk()->assertJsonPath('data.status', 'COMPLETED');

        // parents were notified
        $this->assertGreaterThanOrEqual(3, Notification::where('type', 'targeted')->count());
    }

    #[Test]
    public function driver_cannot_operate_another_drivers_trip()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        $intruder = User::factory()->create(['school_id' => $this->school->id]);
        DriverProfile::create(['school_id' => $this->school->id, 'user_id' => $intruder->id]);

        $this->actingAs($intruder, 'web')
            ->postJson("/api/driver/trips/{$trip->id}/start")
            ->assertStatus(403);
    }

    #[Test]
    public function driver_login_returns_driver_type()
    {
        [, $driverUser] = $this->makeRouteWithDriver();

        $response = $this->postJson('/api/login', [
            'email' => $driverUser->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('type', 'driver');
    }

    #[Test]
    public function non_driver_staff_login_still_returns_admin()
    {
        $response = $this->postJson('/api/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonPath('type', 'admin');
    }

    // ------------------------------------------------------------ parent

    #[Test]
    public function parent_can_track_own_childs_trip_but_not_others()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        // Another school's student must stay invisible.
        $otherSchool = School::create(['name' => ['en' => 'S2'], 'email' => 's2@test.local']);
        $otherStudent = $this->makeStudent($otherSchool->id);

        $parent = fn ($method, $uri) => $this->actingAs($this->parent, 'parent')->{$method . 'Json'}("/api/parent/transportation{$uri}");

        $parent('get', '/children')->assertOk()->assertJsonCount(1, 'data');

        $parent('get', "/children/{$otherStudent->id}/active-trip")->assertStatus(404);

        // Start the trip, send a location, then read live data as the parent.
        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/start");
        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/location", [
            'latitude' => 24.71, 'longitude' => 46.67,
        ]);

        $parent('get', "/children/{$this->student->id}/active-trip")
            ->assertOk()
            ->assertJsonPath('data.status', 'BUS_ON_THE_WAY')
            ->assertJsonPath('data.bus_location.lat', 24.71);

        $parent('get', "/trips/{$trip->id}/bus-location")
            ->assertOk()
            ->assertJsonPath('data.location.lat', 24.71);
    }

    #[Test]
    public function bus_location_is_forbidden_for_unrelated_parent()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/start");

        $otherParent = My_Parent::create([
            'email' => 'p2@test.local',
            'password' => bcrypt('password'),
            'Name_Father' => 'P2',
            'National_ID_Father' => '2',
            'Phone_Father' => '2',
            'Job_Father' => '2',
            'Passport_ID_Father' => '2',
            'Nationality_Father_id' => Nationalitie::create(['Name' => 'N'])->id,
            'Blood_Type_Father_id' => Type_Blood::create(['Name' => 'A+'])->id,
            'Religion_Father_id' => Religion::create(['Name' => 'Islam'])->id,
            'Address_Father' => 'addr',
        ]);

        $this->actingAs($otherParent, 'parent')
            ->getJson("/api/parent/transportation/trips/{$trip->id}/bus-location")
            ->assertStatus(404);
    }

    #[Test]
    public function driver_can_trigger_emergency()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/start");

        $response = $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/emergency", [
            'latitude' => 24.71,
            'longitude' => 46.67,
            'confirmed' => true,
        ]);
        $response->assertOk()->assertJsonPath('data.status', 'EMERGENCY');

        $this->assertDatabaseHas('transportation_trip_events', [
            'trip_id' => $trip->id, 'type' => 'EMERGENCY',
        ]);

        // Admin live tracking shows the emergency.
        $this->actingAs($this->admin, 'api')->getJson('/api/transportation/live')
            ->assertOk()
            ->assertJsonPath('data.0.status', 'EMERGENCY');
    }

    #[Test]
    public function emergency_requires_confirmation()
    {
        [$bus, $driverUser, , $route] = $this->makeRouteWithDriver();
        $trip = $this->makeTrip($route, $bus, $driverUser);

        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/start");

        $this->actingAs($driverUser, 'web')->postJson("/api/driver/trips/{$trip->id}/emergency", [
            'latitude' => 24.71, 'longitude' => 46.67,
        ])->assertStatus(422);
    }
}
