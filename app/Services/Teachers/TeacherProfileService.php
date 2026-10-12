<?php

namespace App\Services\Teachers;

use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Applying changes to teacher master data.
 *
 * Editing a teacher touches contact details, specialisation and salary, so the
 * write path is a sensitive action: an unprivileged caller queues the request
 * and the admin replays it through `apply()` once approved.
 */
class TeacherProfileService
{
    /**
     * Validation rules for a teacher update (mirrors StoreTeachers).
     *
     * @return array<string, string>
     */
    public function updateRules(?int $teacherId = null): array
    {
        return [
            'Email'             => 'required|unique:teachers,Email,'.($teacherId ?? 'NULL'),
            'Name_ar'           => 'required|string|max:255',
            'Name_en'           => 'nullable|string|max:255',
            'Specialization_id' => 'required|exists:specializations,id',
            'Gender_id'         => 'required|exists:genders,id',
            'Joining_Date'      => 'required|date|date_format:Y-m-d',
            'Address'           => 'required|string|max:500',
            'Salary'            => 'nullable|numeric|min:0',
            'Password'          => 'nullable|string|min:6',
        ];
    }

    /**
     * @param  array<string, mixed>  $data  validated teacher payload
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function apply(Teacher $teacher, array $data, ?Model $actor = null): Teacher
    {
        // Re-validate: the request sat in the queue and may be stale now.
        Validator::make($data, $this->updateRules($teacher->getKey()))->validate();

        $teacher->Name = [
            'en' => $data['Name_en'] ?: $data['Name_ar'],
            'ar' => $data['Name_ar'],
        ];
        $teacher->email = $data['Email'];
        $teacher->Specialization_id = $data['Specialization_id'];
        $teacher->Gender_id = $data['Gender_id'];
        $teacher->Joining_Date = $data['Joining_Date'];
        $teacher->Address = $data['Address'];
        $teacher->salary = isset($data['Salary']) && $data['Salary'] !== null && $data['Salary'] !== ''
            ? $data['Salary']
            : null;

        if (! empty($data['Password'])) {
            $teacher->password = Hash::make($data['Password']);
        }

        if ($actor instanceof \App\User && $actor->school_id) {
            $teacher->school_id = $actor->school_id;
        }

        $teacher->save();

        return $teacher->fresh(['specializations', 'genders']);
    }
}