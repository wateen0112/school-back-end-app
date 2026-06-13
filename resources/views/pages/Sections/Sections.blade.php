@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    {{ trans('Sections_trans.title_page') }}
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    {{ trans('Sections_trans.title_page') }}
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row p-4">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-body">
                    <a class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" href="#" data-toggle="modal" data-target="#exampleModal">
                        {{ trans('Sections_trans.add_section') }}</a>
                </div>

                @if ($errors->any())
                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="card card-statistics h-100 bg-surface-container border-0 rounded-lg">
                    <div class="card-body">
                        <div class="accordion gray plus-icon round">

                            @foreach ($Grades as $Grade)

                                <div class="acd-group">
                                    <a href="#" class="acd-heading bg-primary text-on-primary rounded-lg px-4 py-3 transition-all hover:bg-primary-container">{{ $Grade->Name }}</a>
                                    <div class="acd-des">

                                        <div class="row">
                                            <div class="col-xl-12 mb-30">
                                                <div class="card card-statistics h-100 bg-surface-container-lowest border-0 rounded-lg">
                                                    <div class="card-body">
                                                        <div class="d-block d-md-flex justify-content-between">
                                                            <div class="d-block">
                                                            </div>
                                                        </div>
                                                        <div class="table-responsive mt-15 rounded-lg">
                                                            <table class="table center-aligned-table mb-0 rounded-lg">
                                                                <thead>
                                                                <tr class="bg-surface-container text-on-surface-variant">
                                                                    <th class="px-4 py-3 text-label-lg rounded-tl-lg">#</th>
                                                                    <th class="px-4 py-3 text-label-lg">{{ trans('Sections_trans.Name_Section') }}
                                                                    </th>
                                                                    <th class="px-4 py-3 text-label-lg">{{ trans('Sections_trans.Name_Class') }}</th>
                                                                    <th class="px-4 py-3 text-label-lg">{{ trans('Sections_trans.Status') }}</th>
                                                                    <th class="px-4 py-3 text-label-lg rounded-tr-lg">{{ trans('Sections_trans.Processes') }}</th>
                                                                </tr>
                                                                </thead>
                                                                <tbody class="divide-y divide-outline-variant">
                                                                <?php $i = 0; ?>
                                                                @foreach ($Grade->Sections as $list_Sections)
                                                                    <tr class="hover:bg-surface-container-high transition-colors">
                                                                        <?php $i++; ?>
                                                                        <td class="px-4 py-4">
                                                                            <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                                                                                {{ $i }}
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-4 py-4">
                                                                            <div class="flex items-center justify-center gap-2">
                                                                                <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                                                                    {{mb_substr($list_Sections->Name_Section, 0, 2)}}
                                                                                </div>
                                                                                <span class="text-body-md">{{$list_Sections->Name_Section}}</span>
                                                                            </div>
                                                                        </td>
                                                                        <td class="px-4 py-4">
                                                                            <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                                                {{$list_Sections->My_classs->Name_Class}}
                                                                            </span>
                                                                        </td>
                                                                        <td class="px-4 py-4">
                                                                            @if ($list_Sections->Status === 1)
                                                                                <span
                                                                                    class="bg-success/15 text-success px-2 py-1 rounded text-xs">{{ trans('Sections_trans.Status_Section_AC') }}</span>
                                                                            @else
                                                                                <span
                                                                                    class="bg-error/15 text-error px-2 py-1 rounded text-xs">{{ trans('Sections_trans.Status_Section_No') }}</span>
                                                                            @endif

                                                                        </td>
                                                                        <td class="px-4 py-4">
                                                                            <div class="flex items-center justify-center gap-2">
                                                                                <a href="#"
                                                                                   class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105"
                                                                                   data-toggle="modal"
                                                                                   data-target="#edit{{ $list_Sections->id }}">{{ trans('Sections_trans.Edit') }}</a>
                                                                                <a href="#"
                                                                                   class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105"
                                                                                   data-toggle="modal"
                                                                                   data-target="#delete{{ $list_Sections->id }}">{{ trans('Sections_trans.Delete') }}</a>
                                                                            </div>
                                                                        </td>
                                                                    </tr>


                                                                    <!--تعديل قسم جديد -->
                                                                    <div class="modal fade"
                                                                         id="edit{{ $list_Sections->id }}"
                                                                         tabindex="-1" role="dialog"
                                                                         aria-labelledby="exampleModalLabel"
                                                                         aria-hidden="true">
                                                                        <div class="modal-dialog" role="document">
                                                                            <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                                                                <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                                                                                    <h5 class="modal-title text-title-lg mb-0"
                                                                                        style="font-family: 'Cairo', sans-serif;"
                                                                                        id="exampleModalLabel">
                                                                                        {{ trans('Sections_trans.edit_Section') }}
                                                                                    </h5>
                                                                                    <button type="button" class="close text-on-primary hover:text-primary-container transition-colors"
                                                                                            data-dismiss="modal"
                                                                                            aria-label="Close">
                                                                                    <span
                                                                                        aria-hidden="true">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body p-4">

                                                                                    <form
                                                                                        action="{{ route('Sections.update', 'test') }}"
                                                                                        method="POST">
                                                                                        {{ method_field('patch') }}
                                                                                        {{ csrf_field() }}
                                                                                        <div class="row mb-3">
                                                                                            <div class="col-md-6 mb-3">
                                                                                                <input type="text"
                                                                                                       name="Name_Section_Ar"
                                                                                                       class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                                                                       value="{{ $list_Sections->getTranslation('Name_Section', 'ar') }}">
                                                                                            </div>

                                                                                            <div class="col-md-6 mb-3">
                                                                                                <input type="text"
                                                                                                       name="Name_Section_En"
                                                                                                       class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                                                                       value="{{ $list_Sections->getTranslation('Name_Section', 'en') }}">
                                                                                                <input id="id"
                                                                                                       type="hidden"
                                                                                                       name="id"
                                                                                                       class="form-control"
                                                                                                       value="{{ $list_Sections->id }}">
                                                                                            </div>

                                                                                        </div>

                                                                                        <div class="col mb-3">
                                                                                            <label for="inputName"
                                                                                                   class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Grade') }}</label>
                                                                                            <select name="Grade_id"
                                                                                                    class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2"
                                                                                                    onclick="console.log($(this).val())">
                                                                                                <!--placeholder-->
                                                                                                <option
                                                                                                    value="{{ $Grade->id }}">
                                                                                                    {{ $Grade->Name }}
                                                                                                </option>
                                                                                                @foreach ($list_Grades as $list_Grade)
                                                                                                    <option
                                                                                                        value="{{ $list_Grade->id }}">
                                                                                                        {{ $list_Grade->Name }}
                                                                                                    </option>
                                                                                                @endforeach
                                                                                            </select>
                                                                                        </div>

                                                                                        <div class="col mb-3">
                                                                                            <label for="inputName"
                                                                                                   class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Class') }}</label>
                                                                                            <select name="Class_id"
                                                                                                    class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2">
                                                                                                <option
                                                                                                    value="{{ $list_Sections->My_classs->id }}">
                                                                                                    {{ $list_Sections->My_classs->Name_Class }}
                                                                                                </option>
                                                                                            </select>
                                                                                        </div>

                                                                                        <div class="col mb-3">
                                                                                            <div class="form-check">

                                                                                                @if ($list_Sections->Status === 1)
                                                                                                    <input
                                                                                                        type="checkbox"
                                                                                                        checked
                                                                                                        class="form-check-input rounded"
                                                                                                        name="Status"
                                                                                                        id="exampleCheck1">
                                                                                                @else
                                                                                                    <input
                                                                                                        type="checkbox"
                                                                                                        class="form-check-input rounded"
                                                                                                        name="Status"
                                                                                                        id="exampleCheck1">
                                                                                                @endif
                                                                                                <label
                                                                                                    class="form-check-label text-label-lg text-on-surface-variant"
                                                                                                    for="exampleCheck1">{{ trans('Sections_trans.Status') }}</label><br>

                                                                                                    <div class="col mt-3">
                                                                                                        <label for="inputName" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Teacher') }}</label>
                                                                                                        <select multiple name="teacher_id[]" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" id="exampleFormControlSelect2">
                                                                                                            @foreach($list_Sections->teachers as $teacher)
                                                                                                                <option selected value="{{$teacher['id']}}">{{$teacher['Name']}}</option>
                                                                                                            @endforeach

                                                                                                            @foreach($teachers as $teacher)
                                                                                                                <option value="{{$teacher->id}}">{{$teacher->Name}}</option>
                                                                                                            @endforeach
                                                                                                        </select>
                                                                                                    </div>
                                                                                            </div>
                                                                                        </div>


                                                                                </div>
                                                                                <div class="modal-footer border-0">
                                                                                    <button type="button"
                                                                                            class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                                                            data-dismiss="modal">{{ trans('Sections_trans.Close') }}</button>
                                                                                    <button type="submit"
                                                                                            class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Sections_trans.submit') }}</button>
                                                                                </div>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                    </div>


                                                                    <!-- delete_modal_Grade -->
                                                                    <div class="modal fade"
                                                                         id="delete{{ $list_Sections->id }}"
                                                                         tabindex="-1" role="dialog"
                                                                         aria-labelledby="exampleModalLabel"
                                                                         aria-hidden="true">
                                                                        <div class="modal-dialog" role="document">
                                                                            <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                                                                <div class="modal-header bg-error text-on-error rounded-t-xl border-0">
                                                                                    <h5 style="font-family: 'Cairo', sans-serif;"
                                                                                        class="modal-title text-title-lg mb-0"
                                                                                        id="exampleModalLabel">
                                                                                        {{ trans('Sections_trans.delete_Section') }}
                                                                                    </h5>
                                                                                    <button type="button" class="close text-on-error hover:text-error-container transition-colors"
                                                                                            data-dismiss="modal"
                                                                                            aria-label="Close">
                                                                                    <span
                                                                                        aria-hidden="true">&times;</span>
                                                                                    </button>
                                                                                </div>
                                                                                <div class="modal-body p-4">
                                                                                    <form
                                                                                        action="{{ route('Sections.destroy', 'test') }}"
                                                                                        method="post">
                                                                                        {{ method_field('Delete') }}
                                                                                        @csrf
                                                                                        <p class="text-body-md text-on-surface mb-4">{{ trans('Sections_trans.Warning_Section') }}</p>
                                                                                        <input id="id" type="hidden"
                                                                                               name="id"
                                                                                               class="form-control"
                                                                                               value="{{ $list_Sections->id }}">
                                                                                        <div class="modal-footer border-0">
                                                                                            <button type="button"
                                                                                                    class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                                                                    data-dismiss="modal">{{ trans('Sections_trans.Close') }}</button>
                                                                                            <button type="submit"
                                                                                                    class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Sections_trans.submit') }}</button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
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
                                    @endforeach
                                </div>
                        </div>
                    </div>

                    <!--اضافة قسم جديد -->
                    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog"
                         aria-labelledby="exampleModalLabel"
                         aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                                    <h5 class="modal-title text-title-lg mb-0" style="font-family: 'Cairo', sans-serif;"
                                        id="exampleModalLabel">
                                        {{ trans('Sections_trans.add_section') }}</h5>
                                    <button type="button" class="close text-on-primary hover:text-primary-container transition-colors" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body p-4">

                                    <form action="{{ route('Sections.store') }}" method="POST">
                                        {{ csrf_field() }}
                                        <div class="row mb-3">
                                            <div class="col-md-6 mb-3">
                                                <input type="text" name="Name_Section_Ar" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                       placeholder="{{ trans('Sections_trans.Section_name_ar') }}">
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <input type="text" name="Name_Section_En" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                       placeholder="{{ trans('Sections_trans.Section_name_en') }}">
                                            </div>

                                        </div>

                                        <div class="col mb-3">
                                            <label for="inputName"
                                                   class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Grade') }}</label>
                                            <select name="Grade_id" class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2"
                                                    onchange="console.log($(this).val())">
                                                <!--placeholder-->
                                                <option value="" selected
                                                        disabled>{{ trans('Sections_trans.Select_Grade') }}
                                                </option>
                                                @foreach ($list_Grades as $list_Grade)
                                                    <option value="{{ $list_Grade->id }}"> {{ $list_Grade->Name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col mb-3">
                                            <label for="inputName"
                                                   class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Class') }}</label>
                                            <select name="Class_id" class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2">

                                            </select>
                                        </div>

                                        <div class="col mb-3">
                                            <label for="inputName" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Sections_trans.Name_Teacher') }}</label>
                                            <select multiple name="teacher_id[]" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" id="exampleFormControlSelect2">
                                                @foreach($teachers as $teacher)
                                                    <option value="{{$teacher->id}}">{{$teacher->Name}}</option>
                                                @endforeach
                                            </select>
                                        </div>


                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                            data-dismiss="modal">{{ trans('Sections_trans.Close') }}</button>
                                    <button type="submit"
                                            class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Sections_trans.submit') }}</button>
                                </div>
                                </form>
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
            <script>
                $(document).ready(function () {
                    $('select[name="Grade_id"]').on('change', function () {
                        var Grade_id = $(this).val();
                        if (Grade_id) {
                            $.ajax({
                                url: "{{ URL::to('classes') }}/" + Grade_id,
                                type: "GET",
                                dataType: "json",
                                success: function (data) {
                                    $('select[name="Class_id"]').empty();
                                    $.each(data, function (key, value) {
                                        $('select[name="Class_id"]').append('<option value="' + key + '">' + value + '</option>');
                                    });
                                },
                            });
                        } else {
                            console.log('AJAX load did not work');
                        }
                    });
                });

            </script>

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

.form-control {
    border-radius: 8px !important;
}

select {
    border-radius: 8px !important;
}

input[type="checkbox"] {
    border-radius: 4px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-12, .col-md-6, .col {
    border-radius: 8px;
}

/* Accordion styling */
.acd-group {
    border-radius: 8px;
    margin-bottom: 8px;
}

.acd-heading {
    border-radius: 8px !important;
}

.acd-des {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .table, .btn, .modal-content, .modal-header, .modal-footer, .form-control, select, .acd-heading {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

.table tbody tr:hover {
    transform: scale(1.01);
}

.form-control:focus {
    transform: scale(1.02);
}

.acd-heading:hover {
    transform: scale(1.02);
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
