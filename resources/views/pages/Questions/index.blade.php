@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    قائمة الاسئلة
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    قائمة الاسئلة
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
                                <a href="{{route('questions.create')}}" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" role="button"
                                   aria-pressed="true">اضافة سؤال جديد</a><br><br>
                                <div class="table-responsive rounded-lg">
                                    <table id="datatable" class="table table-hover rounded-lg"
                                           data-page-length="50"
                                           style="text-align: center">
                                        <thead>
                                        <tr class="bg-surface-container">
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg">#</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant">السؤال</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant">الاجابات</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant">الاجابة الصحيحة</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant">الدرجة</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant">اسم الاختبار</th>
                                            <th scope="col" class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">العمليات</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-outline-variant">
                                        @foreach($questions as $question)
                                            <tr class="hover:bg-surface-container-high transition-colors">
                                                <td class="px-4 py-4">
                                                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                                                        {{ $loop->iteration}}
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                                            {{mb_substr($question->title, 0, 2)}}
                                                        </div>
                                                        <span class="text-body-md">{{$question->title}}</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$question->answers}}</td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-success/15 text-success px-2 py-1 rounded text-xs">
                                                        {{$question->right_answer}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-warning/15 text-warning px-2 py-1 rounded text-xs">
                                                        {{$question->score}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                        {{$question->quizze->name}}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-4">
                                                    <div class="flex items-center justify-center gap-2">
                                                        <a href="{{route('questions.edit',$question->id)}}"
                                                           class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" role="button" aria-pressed="true"><i
                                                                class="fa fa-edit"></i></a>
                                                        <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105"
                                                                data-toggle="modal"
                                                                data-target="#delete_exam{{ $question->id }}" title="حذف"><i
                                                                class="fa fa-trash"></i></button>
                                                    </div>
                                                </td>
                                            </tr>

                                        @include('pages.Questions.destroy')
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
div, .card, .card-body, .table, .btn {
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
}
</style>
@endpush
