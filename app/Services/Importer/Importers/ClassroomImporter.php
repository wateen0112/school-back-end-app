<?php

namespace App\Services\Importer\Importers;

use App\Models\Classroom;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ClassroomImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'classrooms';
    }

    public function label(): string
    {
        return 'الفصول الدراسية';
    }

    public function columns(): array
    {
        return [
            ['key' => 'Name_Class', 'label' => 'اسم الصف', 'required' => true, 'type' => 'string'],
            ['key' => 'Grade', 'label' => 'المرحلة', 'required' => true, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['Name_Class'] ?? ''))) {
            $errors[] = 'اسم الصف مطلوب';
        }

        if (empty(trim($row['Grade'] ?? ''))) {
            $errors[] = 'المرحلة الدراسية مطلوبة';
        } elseif (ImportLookups::gradeName($row['Grade']) === null) {
            $errors[] = "المرحلة غير موجودة: {$row['Grade']}";
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $gradeId = ImportLookups::gradeName($row['Grade'] ?? '');
        $name = trim($row['Name_Class'] ?? '');

        $id = DB::table('Classrooms')->insertGetId([
            'Name_Class' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'Grade_id' => $gradeId,
        ]);

        return Classroom::find($id);
    }
}
