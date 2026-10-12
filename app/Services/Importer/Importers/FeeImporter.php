<?php

namespace App\Services\Importer\Importers;

use App\Models\Fee;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeeImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'fees';
    }

    public function label(): string
    {
        return 'الرسوم الدراسية';
    }

    public function columns(): array
    {
        return [
            ['key' => 'title', 'label' => 'العنوان', 'required' => true, 'type' => 'string'],
            ['key' => 'amount', 'label' => 'المبلغ', 'required' => true, 'type' => 'int'],
            ['key' => 'Grade', 'label' => 'المرحلة', 'required' => true, 'type' => 'string'],
            ['key' => 'Classroom', 'label' => 'الصف', 'required' => true, 'type' => 'string'],
            ['key' => 'description', 'label' => 'الوصف', 'required' => false, 'type' => 'string'],
            ['key' => 'year', 'label' => 'السنة', 'required' => true, 'type' => 'string'],
            ['key' => 'Fee_type', 'label' => 'نوع الرسوم', 'required' => false, 'type' => 'int'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['title'] ?? ''))) {
            $errors[] = 'العنوان مطلوب';
        }

        if (!isset($row['amount']) || !is_numeric($row['amount'])) {
            $errors[] = 'المبلغ يجب أن يكون رقماً';
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

        if (empty(trim($row['year'] ?? ''))) {
            $errors[] = 'السنة الدراسية مطلوبة';
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $gradeId = ImportLookups::gradeName($row['Grade'] ?? '');
        $classroomId = ImportLookups::classroomName($row['Classroom'] ?? '', $gradeId);

        $id = DB::table('fees')->insertGetId(array_filter([
            'title' => json_encode(['ar' => trim($row['title'] ?? ''), 'en' => trim($row['title'] ?? '')], JSON_UNESCAPED_UNICODE),
            'amount' => $row['amount'] ?? 0,
            'Grade_id' => $gradeId,
            'Classroom_id' => $classroomId,
            'description' => $row['description'] ?? null,
            'year' => $row['year'] ?? date('Y'),
            'Fee_type' => isset($row['Fee_type']) && $row['Fee_type'] !== '' ? (int) $row['Fee_type'] : 1,
        ], fn($v) => $v !== null && $v !== ''));

        return Fee::find($id);
    }
}
