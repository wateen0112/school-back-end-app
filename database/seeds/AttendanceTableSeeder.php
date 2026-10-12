<?php

use App\Models\Attendance;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceTableSeeder extends Seeder
{
    /** Rows per INSERT, so peak memory stays flat no matter how many students there are. */
    private const BATCH_SIZE = 1000;

    /** Students loaded per chunk while streaming. */
    private const CHUNK_SIZE = 50;

    /**
     * Run the database seeds.
     *
     * Deterministic per (student, school day) combination so the seeder
     * stays fast and terminates no matter how few students exist.
     *
     * @return void
     */
    public function run()
    {
        DB::table('attendances')->delete();

        $teacherIds = Teacher::query()->pluck('id')->all();

        if (empty($teacherIds) || ! Student::query()->exists()) {
            return;
        }

        $startDate = strtotime('2026-09-01');
        $endDate = strtotime('2027-05-31');

        if ($endDate < $startDate) {
            return;
        }

        // One school year of attendance per student is ~273 rows, so building the whole
        // set in a single array exhausted the memory limit as soon as the school had more
        // than a handful of students. Insert in fixed-size batches instead.
        $batch = [];
        $now = now();
        $inserted = 0;

        $flush = function () use (&$batch, &$inserted) {
            if ($batch === []) {
                return;
            }

            Attendance::insert($batch);
            $inserted += count($batch);
            $batch = [];
        };

        Student::query()
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($students) use ($startDate, $endDate, $teacherIds, $now, &$batch, $flush) {
                foreach ($students as $student) {
                    for ($day = $startDate; $day <= $endDate; $day += 86400) {
                        $batch[] = [
                            'student_id' => $student->id,
                            'grade_id' => $student->Grade_id,
                            'classroom_id' => $student->Classroom_id,
                            'section_id' => $student->section_id,
                            'teacher_id' => $teacherIds[array_rand($teacherIds)],
                            'attendence_date' => date('Y-m-d', $day),
                            // 80% present
                            'attendence_status' => rand(1, 10) <= 8 ? 1 : 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        if (count($batch) >= self::BATCH_SIZE) {
                            $flush();
                        }
                    }
                }
            });

        $flush();

        if ($inserted > 0) {
            $this->command?->info('Attendance records ready: '.$inserted.'.');
        }
    }
}