<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name_ar' => 'required',
            'name_en' => 'required',
            'email' => 'required|email|unique:students,email,'.$this->id,
            'password' => 'required|string|min:6|max:255',
            'gender_id' => 'required|exists:genders,id',
            'nationalitie_id' => 'required|exists:nationalities,id',
            'blood_id' => 'required|exists:type__bloods,id',
            'Date_Birth' => 'required|date|date_format:Y-m-d',
            'Grade_id' => 'required|exists:Grades,id',
            'Classroom_id' => 'required|exists:Classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'parent_id' => 'required|exists:my__parents,id',
            'academic_year' => 'required',
        ];
    }
}
