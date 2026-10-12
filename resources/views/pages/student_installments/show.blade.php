@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    تفاصيل دفعة الطالب
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    تفاصيل دفعة الطالب
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">بيانات الدفعة</h5>
                        <div>
                            @if($studentInstallment->status != 'paid')
                                <a href="{{ route('receipt_students.create', ['student_installment_id' => $studentInstallment->id, 'student_id' => $studentInstallment->student_id]) }}" class="btn btn-success btn-sm">
                                    <i class="fa fa-money"></i> تسجيل سداد
                                </a>
                            @endif
                            <a href="{{ route('student-installments.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> رجوع
                            </a>
                        </div>
                    </div>

                    <table class="table table-bordered table-sm mb-4">
                        <tbody>
                        <tr>
                            <th style="width: 20%">الطالب</th>
                            <td>{{ $studentInstallment->student->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>خطة التقسيط</th>
                            <td>{{ is_array($studentInstallment->plan->title ?? null) ? ($studentInstallment->plan->title['ar'] ?? $studentInstallment->plan->title['en'] ?? '') : ($studentInstallment->plan->title ?? '-') }}</td>
                        </tr>
                        <tr>
                            <th>الدفعة</th>
                            <td>{{ $studentInstallment->installment->title ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>المبلغ</th>
                            <td>{{ number_format($studentInstallment->amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>المبلغ المسدد</th>
                            <td>{{ number_format($studentInstallment->paid_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>المبلغ المتبقي</th>
                            <td>{{ number_format($studentInstallment->remaining_amount, 2) }}</td>
                        </tr>
                        <tr>
                            <th>الحالة</th>
                            <td>
                                @if($studentInstallment->status == 'paid')
                                    <span class="badge badge-success">مسدد</span>
                                @elseif($studentInstallment->status == 'partial')
                                    <span class="badge badge-warning">مدفوع جزئيا</span>
                                @elseif($studentInstallment->status == 'overdue')
                                    <span class="badge badge-danger">متأخر</span>
                                @else
                                    <span class="badge badge-secondary">معلق</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>تاريخ الاستحقاق</th>
                            <td>{{ $studentInstallment->due_date }}</td>
                        </tr>
                        <tr>
                            <th>تاريخ السداد</th>
                            <td>{{ $studentInstallment->paid_at ?: '-' }}</td>
                        </tr>
                        </tbody>
                    </table>

                    <h5 class="mb-3">سندات القبض المرتبطة</h5>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm table-bordered p-0" style="text-align: center">
                            <thead class="bg-light">
                            <tr>
                                <th>#</th>
                                <th>التاريخ</th>
                                <th>المبلغ</th>
                                <th>البيان</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($studentInstallment->receipts as $receipt)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $receipt->date }}</td>
                                    <td>{{ number_format($receipt->debit, 2) }}</td>
                                    <td>{{ $receipt->description ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center">لا توجد سندات قبض مسجلة</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
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
