@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    خطط التقسيط
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    خطط التقسيط
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100">
                <div class="card-body">
                    <div class="col-xl-12 mb-30">
                        <div class="card card-statistics h-100">
                            <div class="card-body">
                                <a href="{{ route('installment-plans.create') }}" class="btn btn-success btn-sm" role="button" aria-pressed="true">
                                    <i class="fa fa-plus"></i> اضافة خطة تقسيط جديدة
                                </a>
                                <br><br>
                                <div class="table-responsive">
                                    <table id="datatable" class="table table-hover table-sm table-bordered p-0"
                                           data-page-length="50"
                                           style="text-align: center">
                                        <thead>
                                        <tr class="alert-success">
                                            <th>#</th>
                                            <th>العنوان</th>
                                            <th>المرحلة الدراسية</th>
                                            <th>الصف الدراسي</th>
                                            <th>المبلغ الإجمالي</th>
                                            <th>السنة الدراسية</th>
                                            <th>عدد الدفعات</th>
                                            <th>عدد الطلاب</th>
                                            <th>الحالة</th>
                                            <th>العمليات</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @forelse($installmentPlans as $plan)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ is_array($plan->title) ? ($plan->title['ar'] ?? $plan->title['en'] ?? '') : $plan->title }}</td>
                                                <td>{{ $plan->grade->Name ?? '-' }}</td>
                                                <td>{{ $plan->classroom->Name_Class ?? '-' }}</td>
                                                <td>{{ number_format($plan->total_amount, 2) }}</td>
                                                <td>{{ $plan->academic_year }}</td>
                                                <td>{{ $plan->installments_count }}</td>
                                                <td>{{ $plan->students_count }}</td>
                                                <td>
                                                    @if($plan->is_active)
                                                        <span class="badge badge-success">نشط</span>
                                                    @else
                                                        <span class="badge badge-secondary">غير نشط</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('installment-plans.show', $plan->id) }}" class="btn btn-warning btn-sm" title="عرض" role="button" aria-pressed="true">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('installment-plans.edit', $plan->id) }}" class="btn btn-info btn-sm" title="تعديل" role="button" aria-pressed="true">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#delete_plan{{ $plan->id }}" title="حذف">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                    <form action="{{ route('installment-plans.generate', $plan->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm" title="توليد دفعات الطلاب" onclick="return confirm('هل أنت متأكد من توليد دفعات الطلاب؟')">
                                                            <i class="fa fa-refresh"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>

                                            <!-- delete_modal_plan -->
                                            <div class="modal fade" id="delete_plan{{ $plan->id }}" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title" id="exampleModalLabel">حذف خطة تقسيط</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="{{ route('installment-plans.destroy', 'test') }}" method="post">
                                                                {{ method_field('Delete') }}
                                                                @csrf
                                                                <input id="id" type="hidden" name="id" value="{{ $plan->id }}">
                                                                <h5 style="font-family: 'Cairo', sans-serif;">هل انت متاكد من عملية الحذف ؟</h5>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ trans('Students_trans.Close') }}</button>
                                                                    <button type="submit" class="btn btn-danger">{{ trans('Students_trans.submit') }}</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- /delete_modal_plan -->
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center">لا توجد خطط تقسيط</td>
                                            </tr>
                                        @endforelse
                                    </table>
                                </div>
                            </div>
                        </div>
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
