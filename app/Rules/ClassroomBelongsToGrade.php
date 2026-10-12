<?php

namespace App\Rules;

use App\Models\Classroom;
use Illuminate\Contracts\Validation\Rule;

class ClassroomBelongsToGrade implements Rule
{
    protected $gradeId;

    public function __construct($gradeId)
    {
        $this->gradeId = $gradeId;
    }

    public function passes($attribute, $value)
    {
        if (empty($this->gradeId) || empty($value)) {
            return true;
        }

        return Classroom::where('id', $value)
            ->where('Grade_id', $this->gradeId)
            ->exists();
    }

    public function message()
    {
        return trans('validation.classroom_belongs_to_grade');
    }
}
