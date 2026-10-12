<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNotificationRequest extends FormRequest
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
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'type'        => 'required|in:general,targeted,role',
            'target_role' => 'required_if:type,role|in:all,student,teacher,parent,admin',
            'targets'     => 'required_if:type,targeted|array',
            'targets.*.id'   => 'required_with:targets|integer|min:1',
            'targets.*.type' => 'required_with:targets|in:student,teacher,parent,admin',
            'action'      => 'nullable|string|max:50',
            'action_id'   => 'nullable|integer|min:1',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => trans('validation.required'),
            'body.required'  => trans('validation.required'),
            'type.required'  => trans('validation.required'),
            'type.in'        => 'The notification type must be general, targeted, or role.',
            'target_role.required_if' => 'Target role is required when type is role.',
            'target_role.in' => 'Target role must be one of: all, student, teacher, parent, admin.',
            'targets.required_if' => 'Targets array is required when type is targeted.',
            'targets.*.id.required_with' => 'Each target must have an ID.',
            'targets.*.type.required_with' => 'Each target must have a type.',
            'targets.*.type.in' => 'Each target type must be student, teacher, parent, or admin.',
        ];
    }
}
