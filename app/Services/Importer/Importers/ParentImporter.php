<?php

namespace App\Services\Importer\Importers;

use App\Models\My_Parent;
use App\Services\Importer\ImporterInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ParentImporter implements ImporterInterface
{
    public function key(): string
    {
        return 'parents';
    }

    public function label(): string
    {
        return 'أولياء الأمور';
    }

    public function columns(): array
    {
        return [
            ['key' => 'Name_Father', 'label' => 'اسم الأب', 'required' => false, 'type' => 'string'],
            ['key' => 'Email', 'label' => 'البريد الإلكتروني', 'required' => true, 'type' => 'string'],
            ['key' => 'Phone_Father', 'label' => 'هاتف الأب', 'required' => false, 'type' => 'string'],
            ['key' => 'Job_Father', 'label' => 'عمل الأب', 'required' => false, 'type' => 'string'],
            ['key' => 'Name_Mother', 'label' => 'اسم الأم', 'required' => false, 'type' => 'string'],
            ['key' => 'Phone_Mother', 'label' => 'هاتف الأم', 'required' => false, 'type' => 'string'],
            ['key' => 'Job_Mother', 'label' => 'عمل الأم', 'required' => false, 'type' => 'string'],
            ['key' => 'password', 'label' => 'كلمة المرور', 'required' => false, 'type' => 'string'],
        ];
    }

    public function validate(array $row, int $schoolId): ?array
    {
        $errors = [];

        if (empty(trim($row['Email'] ?? ''))) {
            $errors[] = 'البريد الإلكتروني مطلوب';
        }

        return $errors ?: null;
    }

    public function store(array $row, int $schoolId): Model
    {
        $data = [
            'Name_Father' => !empty($row['Name_Father'])
                ? json_encode(['ar' => trim($row['Name_Father']), 'en' => trim($row['Name_Father'])], JSON_UNESCAPED_UNICODE)
                : null,
            'Email' => trim($row['Email'] ?? ''),
            'Phone_Father' => $row['Phone_Father'] ?? null,
            'Job_Father' => !empty($row['Job_Father'])
                ? json_encode(['ar' => trim($row['Job_Father']), 'en' => trim($row['Job_Father'])], JSON_UNESCAPED_UNICODE)
                : null,
            'Name_Mother' => !empty($row['Name_Mother'])
                ? json_encode(['ar' => trim($row['Name_Mother']), 'en' => trim($row['Name_Mother'])], JSON_UNESCAPED_UNICODE)
                : null,
            'Phone_Mother' => $row['Phone_Mother'] ?? null,
            'Job_Mother' => !empty($row['Job_Mother'])
                ? json_encode(['ar' => trim($row['Job_Mother']), 'en' => trim($row['Job_Mother'])], JSON_UNESCAPED_UNICODE)
                : null,
            'password' => !empty($row['password'])
                ? bcrypt($row['password'])
                : bcrypt(Str::random(10)),
            'school_id' => $schoolId,
        ];

        $id = DB::table('my__parents')->insertGetId($data);

        return My_Parent::find($id);
    }
}
