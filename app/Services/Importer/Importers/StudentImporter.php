<?php

namespace App\Services\Importer\Importers;

use App\Models\Student;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'students';
    }

    public function label(): string
    {
        return 'الطلاب';
    }

    public function columns(): array
    {
        return [
            ['key' => 'Name', 'label' => 'الاسم', 'required' => true, 'type' => 'string'],
            ['key' => 'email', 'label' => 'البريد الإلكتروني', 'required' => false, 'type' => 'string'],
            ['key' => 'gender', 'label' => 'الجنس', 'required' => false, 'type' => 'string'],
            ['key' => 'blood_type', 'label' => 'فصيلة الدم', 'required' => false, 'type' => 'string'],
            ['key' => 'nationality', 'label' => 'الجنسية', 'required' => false, 'type' => 'string'],
            ['key' => 'Date_Birth', 'label' => 'تاريخ الميلاد', 'required' => false, 'type' => 'date'],
            ['key' => 'Grade', 'label' => 'المرحلة', 'required' => true, 'type' => 'string'],
            ['key' => 'Classroom', 'label' => 'الصف', 'required' => true, 'type' => 'string'],
            ['key' => 'Section', 'label' => 'القسم', 'required' => false, 'type' => 'string'],
            ['key' => 'Parent_email', 'label' => 'بريد ولي الأمر', 'required' => false, 'type' => 'string'],
            ['key' => 'academic_year', 'label' => 'السنة الدراسية', 'required' => false, 'type' => 'string'],
            ['key' => 'password', 'label' => 'كلمة المرور', 'required' => false, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['Name'] ?? ''))) {
            $errors[] = 'الاسم مطلوب';
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

        if (empty(trim($row['email'] ?? ''))) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        }

        if (!empty($row['Date_Birth'] ?? '') && !preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($row['Date_Birth']))) {
            $errors[] = 'صيغة تاريخ الميلاد غير صحيحة (Y-m-d)';
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $gradeId = ImportLookups::gradeName($row['Grade'] ?? '');
        $classroomId = ImportLookups::classroomName($row['Classroom'] ?? '', $gradeId);
        $sectionId = ImportLookups::sectionName($row['Section'] ?? '', $classroomId)
            ?? (int) ImportLookups::sectionForClassroom($classroomId);

        $genderId = ImportLookups::genderName($row['gender'] ?? '')
            ?? (int) ImportLookups::firstDefault('genders');
        $bloodId = ImportLookups::bloodName($row['blood_type'] ?? '')
            ?? (int) ImportLookups::firstDefault('type__bloods');
        $nationalityId = ImportLookups::nationalityName($row['nationality'] ?? '')
            ?? (int) ImportLookups::firstDefault('nationalities');
        $parentId = ImportLookups::parentEmail($row['Parent_email'] ?? '');

        $email = trim($row['email'] ?? '');
        if ($email === '') {
            $email = 'student_' . Str::lower(Str::random(8)) . '@school.local';
        }

        $data = [
            'name' => json_encode(['ar' => trim($row['Name'] ?? ''), 'en' => trim($row['Name'] ?? '')], JSON_UNESCAPED_UNICODE),
            'email' => $email,
            'password' => !empty($row['password'])
                ? bcrypt($row['password'])
                : bcrypt(Str::random(10)),
            'gender_id' => $genderId,
            'blood_id' => $bloodId,
            'nationalitie_id' => $nationalityId,
            'Date_Birth' => !empty($row['Date_Birth']) ? $row['Date_Birth'] : '2000-01-01',
            'Grade_id' => $gradeId,
            'Classroom_id' => $classroomId,
            'section_id' => $sectionId,
            'parent_id' => $parentId,
            'academic_year' => !empty($row['academic_year']) ? $row['academic_year'] : date('Y'),
            'school_id' => $schoolId,
        ];

        $id = DB::table('students')->insertGetId($data);

        return Student::find($id);
    }
}
