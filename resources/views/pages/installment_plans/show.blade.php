@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    تفاصيل خطة التقسيط
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    تفاصيل خطة التقسيط
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h4>{{ number_format($plan->total_amount, 2) }}</h4>
                                    <p class="mb-0">المبلغ الإجمالي</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $plan->students_count ?? 0 }}</h4>
                                    <p class="mb-0">عدد الطلاب</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h4>{{ $plan->installments_count ?? count($plan->installments) }}</h4>
                                    <p class="mb-0">عدد الدفعات</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h5 class="mb-3">بيانات الخطة</h5>
                    <table class="table table-bordered table-sm mb-4">
                        <tbody>
                        <tr>
                            <th style="width: 20%">العنوان</th>
                            <td>{{ is_array($plan->title) ? ($plan->title['ar'] ?? $plan->title['en'] ?? '') : $plan->title }}</td>
                        </tr>
                        <tr>
                            <th>الوصف</th>
                            <td>{{ is_array($plan->description) ? ($plan->description['ar'] ?? $plan->description['en'] ?? '-') : ($plan->description ?: '-') }}</td>
                        </tr>
                        <tr>
                            <th>المرحلة الدراسية</th>
                            <td>{{ $plan->grade->Name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>الصف الدراسي</th>
                            <td>{{ $plan->classroom->Name_Class ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>نوع الرسوم</th>
                            <td>{{ is_array($plan->fee->title) ? ($plan->fee->title['ar'] ?? $plan->fee->title['en'] ?? '') : ($plan->fee->title ?? '-') }}</td>
                        </tr>
                        <tr>
                            <th>السنة الدراسية</th>
                            <td>{{ $plan->academic_year }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ البداية</th>
                            <td>{{ $plan->start_date }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ النهاية</th>
                            <td>{{ $plan->end_date }}</td>
                        </tr>
                        <tr>
                            <th>الحالة</th>
                            <td>
                                @if($plan->is_active)
                                    <span class="badge badge-success">نشط</span>
                                @else
                                    <span class="badge badge-secondary">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                        </tbody>
                    </table>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">الدفعات</h5>
                        <form action="{{ route('installment-plans.generate', $plan->id) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('هل أنت متأكد من توليد دفعات الطلاب؟')">
                                <i class="fa fa-refresh"></i> توليد دفعات الطلاب
                            </button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-bordered p-0" style="text-align: center">
                            <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>عنوان الدفعة</th>
                                <th>المبلغ</th>
                                <th>تاريخ الاستحقاق</th>
                                <th>الوصف</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($plan->installments as $installment)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $installment->title }}</td>
                                    <td>{{ number_format($installment->amount, 2) }}</td>
                                    <td>{{ $installment->due_date }}</td>
                                    <td>{{ $installment->description ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">لا توجد دفعات مسجلة</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <a href="{{ route('installment-plans.edit', $plan->id) }}" class="btn btn-info btn-sm">
                            <i class="fa fa-edit"></i> تعديل
                        </a>
                        <a href="{{ route('installment-plans.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> رجوع
                        </a>
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
@endsection
