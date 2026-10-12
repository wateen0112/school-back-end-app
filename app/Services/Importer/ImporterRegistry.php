<?php

namespace App\Services\Importer;

use App\Services\Importer\Importers\AttendanceImporter;
use App\Services\Importer\Importers\ClassroomImporter;
use App\Services\Importer\Importers\FeeImporter;
use App\Services\Importer\Importers\ParentImporter;
use App\Services\Importer\Importers\StudentImporter;
use App\Services\Importer\Importers\SubjectImporter;
use App\Services\Importer\Importers\TeacherImporter;

class ImporterRegistry
{
    private static array $importers = [
        'students' => StudentImporter::class,
        'parents' => ParentImporter::class,
        'teachers' => TeacherImporter::class,
        'classrooms' => ClassroomImporter::class,
        'subjects' => SubjectImporter::class,
        'fees' => FeeImporter::class,
        'attendances' => AttendanceImporter::class,
    ];

    public static function get(string $entity): ImporterInterface
    {
        if (!isset(self::$importers[$entity])) {
            throw new \InvalidArgumentException("الكيان غير مدعوم: {$entity}");
        }

        return new self::$importers[$entity]();
    }

    public static function supported(): array
    {
        return [
            'students' => 'الطلاب',
            'parents' => 'أولياء الأمور',
            'teachers' => 'المعلمون',
            'classrooms' => 'الفصول الدراسية',
            'subjects' => 'المقررات',
            'fees' => 'الرسوم الدراسية',
            'attendances' => 'الحضور والغياب',
        ];
    }
}
