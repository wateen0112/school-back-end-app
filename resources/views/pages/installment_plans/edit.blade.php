@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    تعديل خطة تقسيط
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    تعديل خطة تقسيط
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="post" action="{{ route('installment-plans.update', 'test') }}" autocomplete="off" id="installmentPlanForm">
                        @method('PUT')
                        @csrf
                        <input type="hidden" name="id" value="{{ $plan->id }}">

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>العنوان باللغة العربية <span class="text-danger">*</span></label>
                                <input type="text" value="{{ old('title_ar', is_array($plan->title) ? ($plan->title['ar'] ?? '') : $plan->getTranslation('title', 'ar')) }}" name="title_ar" class="form-control" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>العنوان باللغة الانجليزية <span class="text-danger">*</span></label>
                                <input type="text" value="{{ old('title_en', is_array($plan->title) ? ($plan->title['en'] ?? '') : $plan->getTranslation('title', 'en')) }}" name="title_en" class="form-control" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>الوصف باللغة العربية</label>
                                <textarea name="description_ar" class="form-control" rows="2">{{ old('description_ar', is_array($plan->description) ? ($plan->description['ar'] ?? '') : '') }}</textarea>
                            </div>
                            <div class="form-group col-md-6">
                                <label>الوصف باللغة الانجليزية</label>
                                <textarea name="description_en" class="form-control" rows="2">{{ old('description_en', is_array($plan->description) ? ($plan->description['en'] ?? '') : '') }}</textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-3">
                                <label>المرحلة الدراسية <span class="text-danger">*</span></label>
                                <select class="custom-select mr-sm-2" name="grade_id" id="grade_id" required>
                                    @foreach($grades as $grade)
                                        <option value="{{ $grade->id }}" {{ (old('grade_id', $plan->grade_id) == $grade->id) ? 'selected' : '' }}>{{ $grade->Name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>الصف الدراسي <span class="text-danger">*</span></label>
                                <select class="custom-select mr-sm-2" name="classroom_id" id="classroom_id" required>
                                    <option value="{{ $plan->classroom_id }}" selected>{{ $plan->classroom->Name_Class ?? '-' }}</option>
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>نوع الرسوم <span class="text-danger">*</span></label>
                                <select class="custom-select mr-sm-2" name="fee_id" required>
                                    @foreach($fees as $fee)
                                        <option value="{{ $fee->id }}" {{ old('fee_id', $plan->fee_id) == $fee->id ? 'selected' : '' }}>
                                            {{ is_array($fee->title) ? ($fee->title['ar'] ?? $fee->title['en'] ?? '') : $fee->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-3">
                                <label>السنة الدراسية <span class="text-danger">*</span></label>
                                <select class="custom-select mr-sm-2" name="academic_year" required>
                                    @php
                                        $current_year = date("Y");
                                    @endphp
                                    @for($year = $current_year; $year <= $current_year + 1; $year++)
                                        @php
                                            $next_year = $year + 1;
                                            $value = $year . '-' . $next_year;
                                        @endphp
                                        <option value="{{ $value }}" {{ old('academic_year', $plan->academic_year) == $value ? 'selected' : '' }}>{{ $value }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>المبلغ الإجمالي <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" value="{{ old('total_amount', $plan->total_amount) }}" name="total_amount" id="total_amount" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>تاريخ البداية <span class="text-danger">*</span></label>
                                <input type="date" value="{{ old('start_date', $plan->start_date) }}" name="start_date" id="start_date" class="form-control" required>
                            </div>
                            <div class="form-group col-md-4">
                                <label>تاريخ النهاية <span class="text-danger">*</span></label>
                                <input type="date" value="{{ old('end_date', $plan->end_date) }}" name="end_date" id="end_date" class="form-control" required>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">الدفعات</h5>
                            <button type="button" class="btn btn-success btn-sm" id="addInstallmentRow">
                                <i class="fa fa-plus"></i> اضافة دفعة
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-sm" id="installmentsTable">
                                <thead class="bg-light">
                                <tr>
                                    <th>عنوان الدفعة <span class="text-danger">*</span></th>
                                    <th>المبلغ <span class="text-danger">*</span></th>
                                    <th>تاريخ الاستحقاق <span class="text-danger">*</span></th>
                                    <th>الوصف</th>
                                    <th>الحالة</th>
                                    <th>العمليات</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php
                                    $installments = $plan->installments ?? [];
                                @endphp
                                @forelse($installments as $installment)
                                    @php
                                        $isPaid = false;
                                        if (isset($installment->is_paid) && $installment->is_paid) {
                                            $isPaid = true;
                                        }
                                        if (isset($installment->student_installments) && $installment->student_installments->contains(function ($si) {
                                            return in_array($si->status, ['paid', 'partial']);
                                        })) {
                                            $isPaid = true;
                                        }
                                    @endphp
                                    <tr class="installment-row {{ $isPaid ? 'table-secondary' : '' }}">
                                        <td>
                                            <input type="hidden" name="installments[{{ $loop->index }}][id]" value="{{ $installment->id }}">
                                            <input type="text" name="installments[{{ $loop->index }}][title]" class="form-control form-control-sm" value="{{ old('installments.'.$loop->index.'.title', $installment->title) }}" required {{ $isPaid ? 'readonly' : '' }}>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="installments[{{ $loop->index }}][amount]" class="form-control form-control-sm installment-amount" value="{{ old('installments.'.$loop->index.'.amount', $installment->amount) }}" required {{ $isPaid ? 'readonly' : '' }}>
                                        </td>
                                        <td>
                                            <input type="date" name="installments[{{ $loop->index }}][due_date]" class="form-control form-control-sm installment-date" value="{{ old('installments.'.$loop->index.'.due_date', $installment->due_date) }}" required {{ $isPaid ? 'readonly' : '' }}>
                                        </td>
                                        <td>
                                            <input type="text" name="installments[{{ $loop->index }}][description]" class="form-control form-control-sm" value="{{ old('installments.'.$loop->index.'.description', $installment->description) }}" {{ $isPaid ? 'readonly' : '' }}>
                                        </td>
                                        <td>
                                            @if($isPaid)
                                                <span class="badge badge-success">تم السداد</span>
                                            @else
                                                <span class="badge badge-warning">غير مسدد</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if(!$isPaid)
                                                <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="2" class="text-right">المجموع</th>
                                    <th colspan="4">
                                        <span id="installmentsSum" class="text-success">0.00</span>
                                        / <span id="totalAmountDisplay">0.00</span>
                                        <small id="sumMessage" class="ml-2"></small>
                                    </th>
                                </tr>
                                </tfoot>
                            </table>
                        </div>

                        <br>
                        <button type="submit" class="btn btn-primary">{{ trans('Students_trans.submit') }}</button>
                    </form>

                </div>
            </div>
        </div>
    </div>
    <!-- row closed -->
@endsection
@section('js')
    @toastr_js
    @toastr_render

    <script>
        $(document).ready(function () {
            let rowIndex = {{ count($installments) }};

            // Load classrooms when grade changes
            $('select[name="grade_id"]').on('change', function () {
                var grade_id = $(this).val();
                if (grade_id) {
                    $.ajax({
                        url: "{{ URL::to('Get_classrooms') }}/" + grade_id,
                        type: "GET",
                        dataType: "json",
                        success: function (data) {
                            $('select[name="classroom_id"]').empty();
                            $('select[name="classroom_id"]').append('<option selected disabled>{{ trans('Parent_trans.Choose') }}...</option>');
                            $.each(data, function (key, value) {
                                $('select[name="classroom_id"]').append('<option value="' + key + '">' + value + '</option>');
                            });
                        },
                    });
                } else {
                    $('select[name="classroom_id"]').empty();
                    $('select[name="classroom_id"]').append('<option selected disabled>{{ trans('Parent_trans.Choose') }}...</option>');
                }
            });

            // Add installment row
            $('#addInstallmentRow').on('click', function () {
                addRow();
            });

            function addRow() {
                let index = rowIndex++;
                let html = `
                    <tr class="installment-row">
                        <td>
                            <input type="text" name="installments[${index}][title]" class="form-control form-control-sm" required>
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" name="installments[${index}][amount]" class="form-control form-control-sm installment-amount" required>
                        </td>
                        <td>
                            <input type="date" name="installments[${index}][due_date]" class="form-control form-control-sm installment-date" required>
                        </td>
                        <td>
                            <input type="text" name="installments[${index}][description]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <span class="badge badge-warning">غير مسدد</span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-row"><i class="fa fa-trash"></i></button>
                        </td>
                    </tr>
                `;
                $('#installmentsTable tbody').append(html);
                calculateSum();
            }

            // Remove row
            $('#installmentsTable').on('click', '.remove-row', function () {
                $(this).closest('tr').remove();
                calculateSum();
            });

            // Live sum
            $('#installmentsTable').on('input', '.installment-amount', function () {
                calculateSum();
            });

            $('#total_amount').on('input', function () {
                calculateSum();
            });

            function calculateSum() {
                let sum = 0;
                $('.installment-amount').each(function () {
                    let val = parseFloat($(this).val());
                    if (!isNaN(val)) {
                        sum += val;
                    }
                });
                let total = parseFloat($('#total_amount').val()) || 0;
                $('#installmentsSum').text(sum.toFixed(2));
                $('#totalAmountDisplay').text(total.toFixed(2));

                let message = $('#sumMessage');
                if (sum === total && total > 0) {
                    message.html('<span class="text-success"><i class="fa fa-check"></i> المجموع مطابق</span>');
                } else if (total > 0) {
                    let diff = (total - sum).toFixed(2);
                    message.html('<span class="text-danger">الفرق: ' + diff + '</span>');
                } else {
                    message.html('');
                }
            }

            // Validate ascending due dates and sum match before submit
            $('#installmentPlanForm').on('submit', function (e) {
                let total = parseFloat($('#total_amount').val()) || 0;
                let sum = 0;
                let dates = [];
                let valid = true;

                $('.installment-amount').each(function () {
                    let val = parseFloat($(this).val());
                    if (!isNaN(val)) sum += val;
                });

                $('.installment-date').each(function () {
                    let val = $(this).val();
                    if (val) dates.push(val);
                });

                for (let i = 1; i < dates.length; i++) {
                    if (dates[i] < dates[i - 1]) {
                        toastr.error('تواريخ الاستحقاق يجب أن تكون متدرجة بشكل تصاعدي');
                        valid = false;
                        break;
                    }
                }

                if (Math.abs(sum - total) > 0.01) {
                    toastr.error('مجموع الدفعات يجب أن يساوي المبلغ الإجمالي');
                    valid = false;
                }

                if (!valid) {
                    e.preventDefault();
                }
            });

            calculateSum();
        });
    </script>
@endsection
