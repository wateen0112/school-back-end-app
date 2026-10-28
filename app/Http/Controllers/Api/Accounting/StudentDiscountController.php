<?php

namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentDiscountController extends Controller
{
    /**
     * List student discounts. Optionally filter by ?student_id=.
     */
    public function index(Request $request)
    {
        try {
            $query = StudentDiscount::with('student')->latest();

            if ($request->filled('student_id')) {
                $query->where('student_id', (int) $request->student_id);
            }

            return response()->json([
                'success' => true,
                'data' => $query->get()->map(fn ($d) => $this->serialize($d)),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الخصومات',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|exists:students,id',
            'type' => 'required|in:' . implode(',', StudentDiscount::TYPES),
            'value' => 'required|numeric|min:0.01',
            'is_percentage' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
            'year' => 'nullable|string|max:20',
            'years' => 'nullable|array',
            'years.*' => 'string|max:30',
            'active' => 'nullable|boolean',
            'manual_reference_no' => 'nullable|string|max:100',
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الخصم غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->guardValueRange($data);

        $years = $data['years'] ?? null;
        // An empty selection means "apply to all years".
        if (is_array($years) && count($years) === 0) {
            $years = null;
        }

        $discount = StudentDiscount::create([
            'student_id' => (int) $data['student_id'],
            'type' => $data['type'],
            'value' => (float) $data['value'],
            'is_percentage' => (bool) ($data['is_percentage'] ?? false),
            'reason' => $data['reason'] ?? null,
            'year' => $years ? ($years[0] ?? null) : ($data['year'] ?? null),
            'years' => $years,
            'active' => (bool) ($data['active'] ?? true),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة الخصم بنجاح',
            'data' => $this->serialize($discount->load('student')),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $discount = StudentDiscount::find($id);

        if (! $discount) {
            return response()->json([
                'success' => false,
                'message' => 'الخصم غير موجود',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|required|in:' . implode(',', StudentDiscount::TYPES),
            'value' => 'sometimes|required|numeric|min:0.01',
            'is_percentage' => 'nullable|boolean',
            'reason' => 'nullable|string|max:500',
            'year' => 'nullable|string|max:20',
            'years' => 'nullable|array',
            'years.*' => 'string|max:30',
            'active' => 'nullable|boolean',
            'manual_reference_no' => 'nullable|string|max:100',
        ], $this->messages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات الخصم غير صحيحة',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $this->guardValueRange($data);

        if (array_key_exists('years', $data)) {
            $years = $data['years'];
            if (is_array($years) && count($years) === 0) {
                $years = null;
            }
            $data['years'] = $years;
        }

        $discount->update(collect($data)->only([
            'type', 'value', 'is_percentage', 'reason', 'year', 'years', 'active', 'manual_reference_no',
        ])->map(fn ($v, $k) => in_array($k, ['is_percentage', 'active'], true) ? (bool) $v : $v)->all());

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الخصم بنجاح',
            'data' => $this->serialize($discount->load('student')),
        ], 200);
    }

    public function destroy($id)
    {
        $discount = StudentDiscount::find($id);

        if (! $discount) {
            return response()->json([
                'success' => false,
                'message' => 'الخصم غير موجود',
            ], 404);
        }

        $discount->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الخصم بنجاح',
        ], 200);
    }

    /**
     * Available discount types with localized labels (for pickers).
     */
    public function types()
    {
        return response()->json([
            'success' => true,
            'data' => collect(StudentDiscount::TYPES)->map(fn ($type) => [
                'type' => $type,
                'label_ar' => StudentDiscount::TYPE_LABELS[$type]['ar'],
                'label_en' => StudentDiscount::TYPE_LABELS[$type]['en'],
            ])->values(),
        ], 200);
    }

    private function serialize(StudentDiscount $discount): array
    {
        $label = StudentDiscount::TYPE_LABELS[$discount->type]
            ?? ['ar' => $discount->type, 'en' => $discount->type];

        return [
            'id' => (int) $discount->id,
            'student_id' => (int) $discount->student_id,
            'student_name' => $discount->student ? (string) $discount->student->name : null,
            'type' => $discount->type,
            'type_label_ar' => $label['ar'],
            'type_label_en' => $label['en'],
            'value' => (float) $discount->value,
            'is_percentage' => (bool) $discount->is_percentage,
            'reason' => $discount->reason,
            'year' => $discount->year,
            'years' => $discount->years,
            'active' => (bool) $discount->active,
            'invoice_number' => $discount->invoice_number,
            'manual_reference_no' => $discount->manual_reference_no,
        ];
    }

    private function guardValueRange(array $data): void
    {
        if (($data['is_percentage'] ?? false) && (float) $data['value'] > 100) {
            abort(response()->json([
                'success' => false,
                'message' => 'قيمة الخصم بالنسبة المئوية يجب ألا تتجاوز 100',
                'errors' => ['value' => ['قيمة الخصم بالنسبة المئوية يجب ألا تتجاوز 100']],
            ], 422));
        }
    }

    private function messages(): array
    {
        return [
            'student_id.required' => 'الطالب مطلوب',
            'student_id.exists' => 'الطالب المحدد غير موجود',
            'type.required' => 'نوع الخصم مطلوب',
            'type.in' => 'نوع الخصم غير صحيح',
            'value.required' => 'قيمة الخصم مطلوبة',
            'value.numeric' => 'قيمة الخصم يجب أن تكون رقماً',
            'value.min' => 'قيمة الخصم يجب أن تكون أكبر من صفر',
            'reason.max' => 'السبب طويل جداً',
            'year.max' => 'السنة الدراسية غير صحيحة',
            'years.array' => 'السنوات الدراسية غير صحيحة',
            'years.*.string' => 'السنة الدراسية غير صحيحة',
        ];
    }
}
