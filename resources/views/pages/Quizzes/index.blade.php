@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    قائمة الاختبارات
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    قائمة الاختبارات
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
                                <a href="{{route('Quizzes.create')}}" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" role="button"
                                   aria-pressed="true">اضافة اختبار جديد</a><br><br>
                                <div class="table-responsive rounded-lg">
                                    <table id="datatable" class="table table-hover rounded-lg"
                                           data-page-length="50"
                                           style="text-align: center">
                                        <thead>
                                        <tr class="bg-surface-container">
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg">#</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم الاختبار</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم المعلم</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">المرحلة الدراسية</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">الصف الدراسي</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">القسم</th>
                                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">العمليات</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant">
                                        @foreach($quizzes as $quizze)
                                            <tr class="hover:bg-surface-container-high transition-colors">
                                                <td class="px-4 py-4">
                                                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                                                        {{ $loop->iteration}}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                                            {{mb_substr($quizze->name, 0, 2)}}
                                                        </div>
                                                        <span class="text-body-md">{{$quizze->name}}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                        {{$quizze->teacher->Name}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                                                        {{$quizze->grade->Name}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                                        {{$quizze->classroom->Name_Class}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-surface-container px-2 py-1 rounded text-xs text-on-surface">
                                                        {{$quizze->section->Name_Section}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <a href="{{route('Quizzes.edit',$quizze->id)}}"
                                                           class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" role="button" aria-pressed="true"><i
                                                                class="fa fa-edit"></i></a>
                                                        <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105"
                                                                data-toggle="modal"
                                                                data-target="#delete_exam{{ $quizze->id }}" title="حذف"><i
                                                                class="fa fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>

                                            <div class="modal fade" id="delete_exam{{$quizze->id}}" tabindex="-1"
                                                 role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <form action="{{route('Quizzes.destroy','test')}}" method="post">
                                                        {{method_field('delete')}}
                                                        {{csrf_field()}}
                                                        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                                            <div class="modal-header bg-error text-on-error rounded-t-xl border-0">
                                                                <h5 style="font-family: 'Cairo', sans-serif;"
                                                                    class="modal-title text-title-lg mb-0" id="exampleModalLabel">حذف اختبار</h5>
                                                                <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="modal"
                                                                        aria-label="Close">
                                                                    <span aria-hidden="true">&times;</span>
                                                                </button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <p class="text-body-md text-on-surface mb-4"> {{ trans('My_Classes_trans.Warning_Grade') }} {{$quizze->name}}</p>
                                                                <input type="hidden" name="id" value="{{$quizze->id}}">
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                                        data-dismiss="modal">{{ trans('My_Classes_trans.Close') }}</button>
                                                                <button type="submit"
                                                                        class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('My_Classes_trans.submit') }}</button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                        </tbody>
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

.modal-content {
    border-radius: 16px !important;
}

.modal-header {
    border-radius: 16px 16px 0 0 !important;
}

.modal-footer {
    border-radius: 0 0 16px 16px !important;
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
div, .card, .card-body, .table, .btn, .modal-content, .modal-header, .modal-footer {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
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
    
    .modal-content {
        border-radius: 12px !important;
    }
    
    .modal-header {
        border-radius: 12px 12px 0 0 !important;
    }
    
    .modal-footer {
        border-radius: 0 0 12px 12px !important;
    }
}
</style>
@endpush
