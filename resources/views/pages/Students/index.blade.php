@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    {{trans('main_trans.list_students')}}
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    {{trans('main_trans.list_students')}}
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row p-4">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-body">
                    <div class="col-xl-12 mb-30">
                        <div class="card card-statistics h-100 bg-surface-container border-0 rounded-lg">
                            <div class="card-body">
                                <a href="{{route('Students.create')}}" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105 fade-in" role="button"
                                   aria-pressed="true">{{trans('main_trans.add_student')}}</a><br><br>
                                <div class="table-responsive rounded-lg">
                                    <table id="datatable" class="table table-hover fade-in rounded-lg"
                                           data-page-length="50"
                                           style="text-align: center">
                                        <thead>
                                        <tr class="bg-surface-container">
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg">#</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.name')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.email')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.gender')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.Grade')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.classrooms')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{trans('Students_trans.section')}}</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">{{trans('Students_trans.Processes')}}</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant">
                                        @foreach($students as $student)
                                            <tr class="hover:bg-surface-container-high transition-colors">
                                            <td class="px-4 py-4">{{ $loop->index+1 }}</td>
                                            <td class="px-4 py-4">
                                                <div class="flex items-center justify-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs">
                                                        {{mb_substr($student->name, 0, 2)}}
                                                    </div>
                                                    <span class="text-body-md">{{$student->name}}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$student->email}}</td>
                                            <td class="px-4 py-4">
                                                <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                                                    {{$student->gender->Name}}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                                    {{$student->grade->Name}}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                    {{$student->classroom->Name_Class}}
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="bg-surface-container px-2 py-1 rounded text-xs text-on-surface">
                                                    {{$student->section->Name_Section}}
                                                </span>
                                            </td>
                                                <td class="px-4 py-4">
                                                    <div class="dropdown show">
                                                        <a class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-3 py-2 text-sm dropdown-toggle transition-all" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            العمليات
                                                        </a>
                                                        <div class="dropdown-menu bg-surface-container-lowest border-outline-variant rounded-lg shadow-lg" aria-labelledby="dropdownMenuLink">
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('Students.show',$student->id)}}"><i class="far fa-eye text-warning"></i>&nbsp;  عرض بيانات الطالب</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('Students.edit',$student->id)}}"><i class="fa fa-edit text-success"></i>&nbsp;  تعديل بيانات الطالب</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('Fees_Invoices.show',$student->id)}}"><i class="fa fa-edit text-info"></i>&nbsp;اضافة فاتورة رسوم&nbsp;</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('receipt_students.show',$student->id)}}"><i class="fas fa-money-bill-alt text-info"></i>&nbsp; &nbsp;سند قبض</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('ProcessingFee.show',$student->id)}}"><i class="fas fa-money-bill-alt text-info"></i>&nbsp; &nbsp; استبعاد رسوم</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" href="{{route('Payment_students.show',$student->id)}}"><i class="fas fa-donate text-warning"></i>&nbsp; &nbsp;سند صرف</a>
                                                            <a class="dropdown-item text-on-surface hover:bg-surface-container rounded-lg px-3 py-2 transition-colors" data-target="#Delete_Student{{ $student->id }}" data-toggle="modal" href="##Delete_Student{{ $student->id }}"><i class="fa fa-trash text-danger"></i>&nbsp;  حذف بيانات الطالب</a>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @include('pages.Students.Delete')
                                        @endforeach
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

@push('styles')
<style>
/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.card {
    border-radius: 16px !important;
}

.card-body {
    border-radius: 16px !important;
}

.table {
    border-radius: 12px !important;
    overflow: hidden;
}

.table-responsive {
    border-radius: 12px !important;
}

.btn {
    border-radius: 8px !important;
}

.dropdown-menu {
    border-radius: 8px !important;
}

.dropdown-item {
    border-radius: 6px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-12 {
    border-radius: 12px;
}

.col-xl-12 {
    border-radius: 10px;
}

/* Smooth transitions */
div, .card, .card-body, .table, .btn, .dropdown-menu, .dropdown-item {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

.dropdown-item:hover {
    transform: translateX(4px);
}

.table tbody tr:hover {
    transform: scale(1.01);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    div {
        border-radius: 6px;
    }
    
    .card {
        border-radius: 12px !important;
    }
    
    .card-body {
        border-radius: 12px !important;
    }
    
    .table {
        border-radius: 8px !important;
    }
    
    .btn {
        border-radius: 6px !important;
    }
}
</style>
@endpush
