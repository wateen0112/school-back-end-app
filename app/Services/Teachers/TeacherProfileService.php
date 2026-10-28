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
    protected TeacherSalaryPlanService $salaryPlans;

    public function __construct()
    {
        $this->salaryPlans = new TeacherSalaryPlanService();
    }

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
            'Surname'           => 'nullable|string|max:255',
            'Mobile'            => 'nullable|string|max:30',
            'insurance_number'  => 'nullable|string|max:100',
            'Specialization_id' => 'required|exists:specializations,id',
            'Gender_id'         => 'required|exists:genders,id',
            'Joining_Date'      => 'required|date|date_format:Y-m-d',
            'Address'           => 'required|string|max:500',
            'Salary'            => 'nullable|numeric|min:0',
            'salary_type'       => 'nullable|in:monthly,hourly,custom',
            'hour_rate'         => 'required_if:salary_type,hourly|nullable|numeric|min:0.01',
            'custom_salary_amount' => 'required_if:salary_type,custom|nullable|numeric|min:0.01',
            'custom_installments' => 'nullable|array',
            'custom_installments.*.amount' => 'required_with:custom_installments|numeric|min:0.01',
            'custom_installments.*.due_date' => 'required_with:custom_installments|date',
            'custom_installments.*.title' => 'nullable|string|max:255',
            'custom_installments.*.notes' => 'nullable|string',
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
        $teacher->surname = $data['Surname'] ?? null;
        $teacher->mobile = $data['Mobile'] ?? null;
        $teacher->insurance_number = $data['insurance_number'] ?? null;
        $teacher->email = $data['Email'];
        $teacher->Specialization_id = $data['Specialization_id'];
        $teacher->Gender_id = $data['Gender_id'];
        $teacher->Joining_Date = $data['Joining_Date'];
        $teacher->Address = $data['Address'];
        if (! empty($data['Password'])) {
            $teacher->password = Hash::make($data['Password']);
        }

        if ($actor instanceof \App\User && $actor->school_id) {
            $teacher->school_id = $actor->school_id;
        }

        $teacher->save();

        // Salary contract (type / hour rate / custom installments).
        $this->salaryPlans->apply($teacher, $data);

        return $teacher->fresh(['specializations', 'genders', 'customInstallments']);
    }
}