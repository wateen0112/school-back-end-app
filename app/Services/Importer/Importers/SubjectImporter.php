<?php

namespace App\Services\Importer\Importers;

use App\Models\Subject;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SubjectImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'subjects';
    }

    public function label(): string
    {
        return 'المقررات';
    }

    public function columns(): array
    {
        return [
            ['key' => 'Name', 'label' => 'اسم المقرر', 'required' => true, 'type' => 'string'],
            ['key' => 'Grade', 'label' => 'المرحلة', 'required' => true, 'type' => 'string'],
            ['key' => 'Classroom', 'label' => 'الصف', 'required' => true, 'type' => 'string'],
            ['key' => 'Teacher_email', 'label' => 'بريد المعلم', 'required' => false, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['Name'] ?? ''))) {
            $errors[] = 'اسم المقرر مطلوب';
        }

        if (empty(trim($row['Grade'] ?? ''))) {
            $errors[] = 'المرحلة الدراسية مطلوبة';
        } elseif (ImportLookups::gradeName($row['Grade']) === null) {
            $errors[] = "المرحلة غير موجودة: {$row['Grade']}";
        }

        if (empty(trim($row['Classroom'] ?? ''))) {
            $errors[] = 'الصف الدراسي مطلوب';
        } else {
            $gradeId = ImportLookups::gradeName($row['Grade'] ?? '');
            if (ImportLookups::classroomName($row['Classroom'], $gradeId) === null) {
                $errors[] = "الصف غير موجود: {$row['Classroom']}";
            }
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $gradeId = ImportLookups::gradeName($row['Grade'] ?? '');
        $classroomId = ImportLookups::classroomName($row['Classroom'] ?? '', $gradeId);
        $teacherId = ImportLookups::teacherEmail($row['Teacher_email'] ?? '')
            ?? (int) ImportLookups::firstTeacher();
        $name = trim($row['Name'] ?? '');

        $id = DB::table('subjects')->insertGetId([
            'Name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'Grade_id' => $gradeId,
            'Classroom_id' => $classroomId,
            'teacher_id' => $teacherId,
        ]);

        return Subject::find($id);
    }
}
