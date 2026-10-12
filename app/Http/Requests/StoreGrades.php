<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Route;

class StoreGrades extends FormRequest
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
     * Works with any route parameter name (e.g., {grade}, {id}, etc.)
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = $this->getRouteId();
        
        return [
            'Name' => 'required|unique:grades,name->ar,'.$id,
            'Name_en' => 'nullable|unique:grades,name->en,'.$id,
        ];
    }

    public function messages()
    {
        return [
            'Name.required' => trans('validation.required'),
            'Name.unique' => trans('validation.unique'),
            'Name_en.unique' => trans('validation.unique'),
        ];
    }
}
