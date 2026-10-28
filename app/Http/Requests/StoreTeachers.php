<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeachers extends FormRequest
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
     * Works with any route parameter name (e.g., {teacher}, {id}, etc.)
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
            'Email' => 'required|unique:teachers,Email,'.$id,
            'Name_ar' => 'required',
            'Name_en' => 'nullable',
            'Surname' => 'nullable|string|max:255',
            'Mobile' => 'nullable|string|max:30',
            'insurance_number' => 'nullable|string|max:100',
            'Specialization_id' => 'required|exists:specializations,id',
            'Gender_id' => 'required|exists:genders,id',
            'Joining_Date' => 'required|date|date_format:Y-m-d',
            'Address' => 'required',
            'Salary' => 'nullable|numeric|min:0',
            'salary_type' => 'nullable|in:monthly,hourly,custom',
            'hour_rate' => 'required_if:salary_type,hourly|nullable|numeric|min:0.01',
            'custom_salary_amount' => 'required_if:salary_type,custom|nullable|numeric|min:0.01',
            'custom_installments' => 'nullable|array',
            'custom_installments.*.amount' => 'required_with:custom_installments|numeric|min:0.01',
            'custom_installments.*.due_date' => 'required_with:custom_installments|date',
            'custom_installments.*.title' => 'nullable|string|max:255',
            'custom_installments.*.notes' => 'nullable|string',
        ];

        // Password is required for store, optional for update
        if ($this->isUpdate()) {
            $rules['Password'] = 'nullable';
        } else {
            $rules['Password'] = 'required';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'Email.required' => trans('validation.required'),
            'Email.unique' => trans('validation.unique'),
            'Password.required' => trans('validation.required'),
            'Name_ar.required' => trans('validation.required'),
            'Specialization_id.required' => trans('validation.required'),
            'Gender_id.required' => trans('validation.required'),
            'Joining_Date.required' => trans('validation.required'),
            'Address.required' => trans('validation.required'),
        ];
    }
}
