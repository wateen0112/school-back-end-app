@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    قائمة الحضور والغياب للطلاب
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    قائمة الحضور والغياب للطلاب
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row p-4">
        <div class="col-md-12">
            <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-body p-4">

    @if ($errors->any())
        <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (session('status'))
        <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
            <ul class="mb-0">
                <li>{{ session('status') }}</li>
            </ul>
        </div>
    @endif

    <div class="bg-surface-container rounded-lg p-4 mb-4">
        <h5 class="text-title-lg text-primary mb-0" style="font-family: 'Cairo', sans-serif;"> تاريخ اليوم : {{ date('Y-m-d') }}</h5>
    </div>
    
    <form method="post" action="{{ route('Attendance.store') }}">

        @csrf
        <div class="table-responsive rounded-lg">
            <table id="datatable" class="table table-hover rounded-lg" data-page-length="50"
                   style="text-align: center">
                <thead>
                <tr class="bg-success-container">
                    <th class="px-4 py-3 text-label-lg text-on-success rounded-tl-lg">#</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.name') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.email') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.gender') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.Grade') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.classrooms') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success">{{ trans('Students_trans.section') }}</th>
                    <th class="px-4 py-3 text-label-lg text-on-success rounded-tr-lg">{{ trans('Students_trans.Processes') }}</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-outline-variant">
                @foreach ($students as $student)
                    <tr class="hover:bg-surface-container-high transition-colors">
                        <td class="px-4 py-4">
                            <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                                {{ $loop->index + 1 }}
                            </div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="flex items-center justify-center gap-2">
                                <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                    {{mb_substr($student->name, 0, 2)}}
                                </div>
                                <span class="text-body-md">{{$student->name}}</span>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$student->email}}</td>
                        <td class="px-4 py-4">
                            <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                {{$student->gender->Name}}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                                {{$student->grade->Name}}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                {{$student->classroom->Name_Class}}
                            </span>
                        </td>
                        <td class="px-4 py-4">
                            <span class="bg-surface-container px-2 py-1 rounded text-xs text-on-surface">
                                {{$student->section->Name_Section}}
                            </span>
                        </td>
                        <td class="px-4 py-4">

                            @if(isset($student->attendance()->where('attendence_date',date('Y-m-d'))->first()->student_id))

                                <div class="flex items-center justify-center gap-4">
                                    <label class="flex items-center gap-2 text-label-md text-on-surface-variant bg-surface-container rounded-lg px-3 py-2">
                                        <input name="attendences[{{ $student->id }}]" disabled
                                               {{ $student->attendance()->first()->attendence_status == 1 ? 'checked' : '' }}
                                               class="w-4 h-4 text-success" type="radio" value="presence">
                                        <span class="text-success font-medium">حضور</span>
                                    </label>

                                    <label class="flex items-center gap-2 text-label-md text-on-surface-variant bg-surface-container rounded-lg px-3 py-2">
                                        <input name="attendences[{{ $student->id }}]" disabled
                                               {{ $student->attendance()->first()->attendence_status == 0 ? 'checked' : '' }}
                                               class="w-4 h-4 text-error" type="radio" value="absent">
                                        <span class="text-error font-medium">غياب</span>
                                    </label>
                                </div>

                            @else

                                <div class="flex items-center justify-center gap-4">
                                    <label class="flex items-center gap-2 text-label-md text-on-surface-variant hover:bg-success-container/20 rounded-lg px-3 py-2 transition-colors cursor-pointer">
                                        <input name="attendences[{{ $student->id }}]" 
                                               class="w-4 h-4 text-success" type="radio"
                                               value="presence">
                                        <span class="text-success font-medium">حضور</span>
                                    </label>

                                    <label class="flex items-center gap-2 text-label-md text-on-surface-variant hover:bg-error-container/20 rounded-lg px-3 py-2 transition-colors cursor-pointer">
                                        <input name="attendences[{{ $student->id }}]" 
                                               class="w-4 h-4 text-error" type="radio"
                                               value="absent">
                                        <span class="text-error font-medium">غياب</span>
                                    </label>
                                </div>

                            @endif

                            <input type="hidden" name="student_id[]" value="{{ $student->id }}">
                            <input type="hidden" name="grade_id" value="{{ $student->Grade_id }}">
                            <input type="hidden" name="classroom_id" value="{{ $student->Classroom_id }}">
                            <input type="hidden" name="section_id" value="{{ $student->section_id }}">

                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4 text-center">
            <button class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-6 py-3 transition-all transform hover:scale-105" type="submit">{{ trans('Students_trans.submit') }}</button>
        </div>
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

.alert {
    border-radius: 8px !important;
}

label {
    border-radius: 8px;
}

input[type="radio"] {
    border-radius: 4px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-12 {
    border-radius: 12px;
}

/* Smooth transitions */
div, .card, .card-body, .table, .btn, .alert, label {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

label:hover {
    transform: scale(1.05);
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
