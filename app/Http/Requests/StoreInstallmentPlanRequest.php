<?php

namespace App\Http\Requests;

use App\Rules\ClassroomBelongsToGrade;
use App\Rules\InstallmentDatesAreSequential;
use App\Rules\InstallmentSumMatchesTotal;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstallmentPlanRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required',
            'description' => 'nullable',
            'grade_id' => 'required|exists:Grades,id',
            'classroom_id' => [
                'required',
                'exists:Classrooms,id',
                new ClassroomBelongsToGrade($this->input('grade_id')),
            ],
            'fee_id' => 'required|exists:fees,id',
            'total_amount' => 'required|numeric|min:0.01',
            'academic_year' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'nullable|boolean',
            'installments' => [
                'required',
                'array',
                'min:1',
                new InstallmentSumMatchesTotal($this->input('total_amount')),
                new InstallmentDatesAreSequential(),
            ],
            'installments.*.title' => 'required|string',
            'installments.*.amount' => 'required|numeric|min:0',
            'installments.*.due_date' => 'required|date',
            'installments.*.description' => 'nullable|string',
            'installments.*.sort_order' => 'nullable|integer',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => trans('validation.required'),
            'grade_id.required' => trans('validation.required'),
            'classroom_id.required' => trans('validation.required'),
            'fee_id.required' => trans('validation.required'),
            'total_amount.required' => trans('validation.required'),
            'academic_year.required' => trans('validation.required'),
            'start_date.required' => trans('validation.required'),
            'end_date.required' => trans('validation.required'),
            'installments.required' => trans('validation.required'),
        ];
    }
}
