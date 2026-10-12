<?php

namespace App\Services\Importer\Importers;

use App\Models\Attendance;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AttendanceImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'attendances';
    }

    public function label(): string
    {
        return 'الحضور والغياب';
    }

    public function columns(): array
    {
        return [
            ['key' => 'student_email', 'label' => 'بريد الطالب', 'required' => true, 'type' => 'string'],
            ['key' => 'attendence_date', 'label' => 'التاريخ', 'required' => true, 'type' => 'date'],
            ['key' => 'attendence_status', 'label' => 'الحالة', 'required' => true, 'type' => 'string'],
            ['key' => 'teacher_email', 'label' => 'بريد المعلم', 'required' => true, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['student_email'] ?? ''))) {
            $errors[] = 'بريد الطالب مطلوب';
        } elseif (ImportLookups::studentEmail($row['student_email'], $schoolId) === null) {
            $errors[] = "الطالب غير موجود: {$row['student_email']}";
        }

        if (empty(trim($row['attendence_date'] ?? ''))) {
            $errors[] = 'التاريخ مطلوب';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($row['attendence_date']))) {
            $errors[] = 'صيغة التاريخ غير صحيحة (Y-m-d)';
        }

        if (empty(trim($row['attendence_status'] ?? ''))) {
            $errors[] = 'حالة الحضور مطلوبة';
        }

        if (empty(trim($row['teacher_email'] ?? ''))) {
            $errors[] = 'بريد المعلم مطلوب';
        } elseif (ImportLookups::teacherEmail($row['teacher_email']) === null) {
            $errors[] = "المعلم غير موجود: {$row['teacher_email']}";
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $student = DB::table('students')
            ->where('email', trim($row['student_email'] ?? ''))
            ->where('school_id', $schoolId)
            ->first();
        $teacherId = ImportLookups::teacherEmail($row['teacher_email'] ?? '');

        $status = strtolower(trim($row['attendence_status']));
        $statusValue = match ($status) {
            '1', 'present', 'حاضر', 'حضور' => 1,
            '0', 'absent', 'غائب', 'غياب' => 0,
            default => $status,
        };

        $data = array_filter([
            'student_id' => $student?->id,
            'grade_id' => $student?->Grade_id ?? null,
            'classroom_id' => $student?->Classroom_id ?? null,
            'section_id' => $student?->section_id ?? null,
            'teacher_id' => $teacherId,
            'attendence_date' => $row['attendence_date'] ?? '',
            'attendence_status' => $statusValue,
        ], fn($v) => $v !== null && $v !== '');

        $id = DB::table('attendances')->insertGetId($data);

        return Attendance::find($id);
    }
}
