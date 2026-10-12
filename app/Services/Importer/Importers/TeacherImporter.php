<?php

namespace App\Services\Importer\Importers;

use App\Models\Teacher;
use App\Services\Importer\ImporterInterface;
use App\Services\Importer\ImportLookups;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeacherImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'teachers';
    }

    public function label(): string
    {
        return 'المعلمون';
    }

    public function columns(): array
    {
        return [
            ['key' => 'Name', 'label' => 'الاسم', 'required' => true, 'type' => 'string'],
            ['key' => 'Email', 'label' => 'البريد الإلكتروني', 'required' => true, 'type' => 'string'],
            ['key' => 'Specialization', 'label' => 'التخصص', 'required' => false, 'type' => 'string'],
            ['key' => 'Joining_Date', 'label' => 'تاريخ الالتحاق', 'required' => false, 'type' => 'date'],
            ['key' => 'Address', 'label' => 'العنوان', 'required' => false, 'type' => 'string'],
            ['key' => 'password', 'label' => 'كلمة المرور', 'required' => false, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['Name'] ?? ''))) {
            $errors[] = 'الاسم مطلوب';
        }

        if (empty(trim($row['Email'] ?? ''))) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $specializationId = ImportLookups::specializationName($row['Specialization'] ?? '')
            ?? (int) ImportLookups::firstDefault('specializations');
        $genderId = (int) ImportLookups::firstDefault('genders');
        $name = trim($row['Name'] ?? '');

        $data = [
            'Name' => json_encode(['ar' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE),
            'Email' => trim($row['Email'] ?? ''),
            'Specialization_id' => $specializationId,
            'Gender_id' => $genderId,
            'Joining_Date' => $row['Joining_Date'] ?? now()->toDateString(),
            'Address' => $row['Address'] ?? null,
            'school_id' => $schoolId,
            'password' => !empty($row['password'])
                ? bcrypt($row['password'])
                : bcrypt(Str::random(10)),
        ];

        $id = DB::table('teachers')->insertGetId($data);

        return Teacher::find($id);
    }
}
