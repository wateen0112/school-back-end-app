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
     * Get the ID of the record being updated from route parameters.
     * Works with any route parameter name (e.g., {student}, {id}, etc.)
     *
     * @return int|string|null
     */
    protected function getRouteId()
    {
        // Try to get ID from route parameters
        $route = $this->route();
        if ($route) {
            $parameters = $route->parameters();
            // Return the first numeric parameter (which is typically the ID)
            foreach ($parameters as $key => $value) {
                if (is_numeric($value)) {
                    return $value;
                }
            }
        }
        // Fallback to input 'id'
        return $this->input('id');
    }

    /**
     * Check if this is an update request (has route ID)
     *
     * @return bool
     */
    protected function isUpdate()
    {
        return !is_null($this->getRouteId());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->getRouteId();
        
        $rules = [
            'name_ar' => 'required',
            'name_en' => 'nullable',
            'second_name' => 'nullable|string|max:255',
            'grandfather_name' => 'nullable|string|max:255',
            'national_id' => 'nullable|string|max:50',
            'general_record_number' => 'nullable|string|max:100',
            'residence_address' => 'nullable|string|max:255',
            'email' => 'required|email|unique:students,email,'.$id,
            'gender_id' => 'required|exists:genders,id',
            'nationalitie_id' => 'required|exists:nationalities,id',
            'blood_id' => 'required|exists:type__bloods,id',
            'Date_Birth' => 'required|date|date_format:Y-m-d',
            'Grade_id' => 'required|exists:Grades,id',
            'Classroom_id' => 'required|exists:Classrooms,id',
            'section_id' => 'required|exists:sections,id',
            'parent_id' => 'required|exists:my__parents,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'academic_year' => 'nullable',
        ];

        // Password is required for store, optional for update
        if ($this->isUpdate()) {
            $rules['password'] = 'nullable|string|min:6|max:255';
        } else {
            $rules['password'] = 'required|string|min:6|max:255';
        }

        return $rules;
    }
}
