@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    دفعات الطلاب
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    دفعات الطلاب
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">

                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" action="{{ route('student-installments.index') }}">
                                <div class="form-row align-items-end">
                                    <div class="form-group col-md-2">
                                        <label>خطة التقسيط</label>
                                        <select class="custom-select mr-sm-2" name="plan_id">
                                            <option value="">الكل</option>
                                            @foreach($plans as $plan)
                                                <option value="{{ $plan->id }}" {{ request('plan_id') == $plan->id ? 'selected' : '' }}>
                                                    {{ is_array($plan->title) ? ($plan->title['ar'] ?? $plan->title['en'] ?? '') : $plan->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>الحالة</label>
                                        <select class="custom-select mr-sm-2" name="status">
                                            <option value="">الكل</option>
                                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>معلق</option>
                                            <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>مدفوع جزئيا</option>
                                            <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>مسدد</option>
                                            <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>متأخر</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>المرحلة الدراسية</label>
                                        <select class="custom-select mr-sm-2" name="grade_id" id="grade_id">
                                            <option value="">الكل</option>
                                            @foreach($grades as $grade)
                                                <option value="{{ $grade->id }}" {{ request('grade_id') == $grade->id ? 'selected' : '' }}>{{ $grade->Name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>الصف الدراسي</label>
                                        <select class="custom-select mr-sm-2" name="classroom_id" id="classroom_id">
                                            <option value="">الكل</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <label>الطالب</label>
                                        <select class="custom-select mr-sm-2" name="student_id">
                                            <option value="">الكل</option>
                                            @foreach($students as $student)
                                                <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>{{ $student->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group col-md-2">
                                        <button type="submit" class="btn btn-primary btn-block">
                                            <i class="fa fa-search"></i> بحث
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="datatable" class="table table-hover table-sm table-bordered p-0"
                               data-page-length="50"
                               style="text-align: center">
                            <thead>
                            <tr class="alert-success">
                                <th>#</th>
                                <th>اسم الطالب</th>
                                <th>خطة التقسيط</th>
                                <th>الدفعة</th>
                                <th>المبلغ</th>
                                <th>المسدد</th>
                                <th>المتبقي</th>
                                <th>الحالة</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>العمليات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($studentInstallments as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->student->name ?? '-' }}</td>
                                    <td>{{ is_array($item->plan->title ?? null) ? ($item->plan->title['ar'] ?? $item->plan->title['en'] ?? '') : ($item->plan->title ?? '-') }}</td>
                                    <td>{{ $item->installment->title ?? '-' }}</td>
                                    <td>{{ number_format($item->amount, 2) }}</td>
                                    <td>{{ number_format($item->paid_amount, 2) }}</td>
                                    <td>{{ number_format($item->remaining_amount, 2) }}</td>
                                    <td>
                                        @if($item->status == 'paid')
                                            <span class="badge badge-success">مسدد</span>
                                        @elseif($item->status == 'partial')
                                            <span class="badge badge-warning">مدفوع جزئيا</span>
                                        @elseif($item->status == 'overdue')
                                            <span class="badge badge-danger">متأخر</span>
                                        @else
                                            <span class="badge badge-secondary">معلق</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->due_date }}</td>
                                    <td>
                                        <a href="{{ route('student-installments.show', $item->id) }}" class="btn btn-warning btn-sm" title="عرض" role="button" aria-pressed="true">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        @if($item->status != 'paid')
                                            <a href="{{ route('receipt_students.create', ['student_installment_id' => $item->id, 'student_id' => $item->student_id]) }}" class="btn btn-success btn-sm" title="تسجيل سداد" role="button" aria-pressed="true">
                                                <i class="fa fa-money"></i>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center">لا توجد دفعات للطلاب</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        {{ $studentInstallments->appends(request()->query())->links() }}
                    </div>

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
            $('select[name="grade_id"]').on('change', function () {
                var grade_id = $(this).val();
                if (grade_id) {
                    $.ajax({
                        url: "{{ URL::to('Get_classrooms') }}/" + grade_id,
                        type: "GET",
                        dataType: "json",
                        success: function (data) {
                            $('select[name="classroom_id"]').empty();
                            $('select[name="classroom_id"]').append('<option value="">الكل</option>');
                            $.each(data, function (key, value) {
                                var selected = (key == '{{ request('classroom_id') }}') ? 'selected' : '';
                                $('select[name="classroom_id"]').append('<option value="' + key + '" ' + selected + '>' + value + '</option>');
                            });
                        },
                    });
                } else {
                    $('select[name="classroom_id"]').empty();
                    $('select[name="classroom_id"]').append('<option value="">الكل</option>');
                }
            });

            @if(request('grade_id'))
                $('select[name="grade_id"]').trigger('change');
            @endif
        });
    </script>
@endsection
