@extends('layouts.master')
@section('css')
    @toastr_css


@section('title')
    {{ trans('My_Classes_trans.title_page') }}
@stop
@endsection
@section('page-header')
<!-- breadcrumb -->
@section('PageTitle')
{{ trans('My_Classes_trans.title_page') }}
@stop
<!-- breadcrumb -->
@endsection
@section('content')
<!-- row -->
<div class="row p-4">

<div class="col-xl-12 mb-30">
    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
        <div class="card-body">

            @if ($errors->any())
                <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <button type="button" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" data-toggle="modal" data-target="#exampleModal">
                {{ trans('My_Classes_trans.add_class') }}
            </button>

                <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" id="btn_delete_all">
                    {{ trans('My_Classes_trans.delete_checkbox') }}
                </button>


            <br><br>

                <form action="{{ route('Filter_Classes') }}" method="POST">
                    {{ csrf_field() }}
                    <select class="selectpicker bg-surface-container border-outline-variant text-on-surface rounded-lg px-4 py-2" data-style="btn-info" name="Grade_id" required
                            onchange="this.form.submit()">
                        <option value="" selected disabled>{{ trans('My_Classes_trans.Search_By_Grade') }}</option>
                        @foreach ($Grades as $Grade)
                            <option value="{{ $Grade->id }}">{{ $Grade->Name }}</option>
                        @endforeach
                    </select>
                </form>



            <div class="table-responsive rounded-lg">
                <table id="datatable" class="table table-hover rounded-lg" data-page-length="50"
                    style="text-align: center">
                    <thead>
                        <tr class="bg-surface-container">
                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg"><input name="select_all" id="example-select-all" type="checkbox" onclick="CheckAll('box1', this)" /></th>
                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">#</th>
                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('My_Classes_trans.Name_class') }}</th>
                            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('My_Classes_trans.Name_Grade') }}</th>
                            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">{{ trans('My_Classes_trans.Processes') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">

                    @if (isset($details))

                        <?php $List_Classes = $details; ?>
                    @else

                        <?php $List_Classes = $My_Classes; ?>
                    @endif

                        <?php $i = 0; ?>

                        @foreach ($List_Classes as $My_Class)
                            <tr class="hover:bg-surface-container-high transition-colors">
                                <?php $i++; ?>
                                <td class="px-4 py-4"><input type="checkbox"  value="{{ $My_Class->id }}" class="box1 rounded" ></td>
                                <td class="px-4 py-4">
                                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                                        {{ $i }}
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                            {{mb_substr($My_Class->Name_Class, 0, 2)}}
                                        </div>
                                        <span class="text-body-md">{{$My_Class->Name_Class}}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                        {{$My_Class->Grades->Name}}
                                    </span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" data-toggle="modal"
                                            data-target="#edit{{ $My_Class->id }}"
                                            title="{{ trans('Grades_trans.Edit') }}"><i class="fa fa-edit"></i></button>
                                        <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" data-toggle="modal"
                                            data-target="#delete{{ $My_Class->id }}"
                                            title="{{ trans('Grades_trans.Delete') }}"><i
                                                class="fa fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>

                            <!-- edit_modal_Grade -->
                            <div class="modal fade" id="edit{{ $My_Class->id }}" tabindex="-1" role="dialog"
                                 aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                        <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                                            <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0"
                                                id="exampleModalLabel">
                                                {{ trans('My_Classes_trans.edit_class') }}
                                            </h5>
                                            <button type="button" class="close text-on-primary hover:text-primary-container transition-colors" data-dismiss="modal"
                                                    aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <!-- edit_form -->
                                            <form action="{{ route('Classrooms.update', 'test') }}" method="post">
                                                {{ method_field('patch') }}
                                                @csrf
                                                <div class="row mb-3">
                                                    <div class="col-md-6 mb-3">
                                                        <label for="Name"
                                                               class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_class') }}
                                                            :</label>
                                                        <input id="Name" type="text" name="Name"
                                                               class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                               value="{{ $My_Class->getTranslation('Name_Class', 'ar') }}"
                                                               required>
                                                        <input id="id" type="hidden" name="id" class="form-control"
                                                               value="{{ $My_Class->id }}">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label for="Name_en"
                                                               class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_class_en') }}
                                                            :</label>
                                                        <input type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                               value="{{ $My_Class->getTranslation('Name_Class', 'en') }}"
                                                               name="Name_en" required>
                                                    </div>
                                                </div>
                                                <div class="form-group mb-4">
                                                    <label
                                                        for="exampleFormControlTextarea1" class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_Grade') }}
                                                        :</label>
                                                    <select class="form-control form-control-lg bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                            id="exampleFormControlSelect1" name="Grade_id">
                                                        <option value="{{ $My_Class->Grades->id }}">
                                                            {{ $My_Class->Grades->Name }}
                                                        </option>
                                                        @foreach ($Grades as $Grade)
                                                            <option value="{{ $Grade->id }}">
                                                                {{ $Grade->Name }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                </div>

                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                            data-dismiss="modal">{{ trans('Grades_trans.Close') }}</button>
                                                    <button type="submit"
                                                            class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Grades_trans.submit') }}</button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>
                            </div>


                            <!-- delete_modal_Grade -->
                            <div class="modal fade" id="delete{{ $My_Class->id }}" tabindex="-1" role="dialog"
                                 aria-labelledby="exampleModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                        <div class="modal-header bg-error text-on-error rounded-t-xl border-0">
                                            <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0"
                                                id="exampleModalLabel">
                                                {{ trans('My_Classes_trans.delete_class') }}
                                            </h5>
                                            <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="modal"
                                                    aria-label="Close">
                                                <span aria-hidden="true">&times;</span>
                                            </button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <form action="{{ route('Classrooms.destroy', 'test') }}"
                                                  method="post">
                                                {{ method_field('Delete') }}
                                                @csrf
                                                <p class="text-body-md text-on-surface mb-4">{{ trans('My_Classes_trans.Warning_Grade') }}</p>
                                                <input id="id" type="hidden" name="id" class="form-control"
                                                       value="{{ $My_Class->id }}">
                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                            data-dismiss="modal">{{ trans('My_Classes_trans.Close') }}</button>
                                                    <button type="submit"
                                                            class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('My_Classes_trans.submit') }}</button>
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


<!-- add_modal_class -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
            <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0" id="exampleModalLabel">
                    {{ trans('My_Classes_trans.add_class') }}
                </h5>
                <button type="button" class="close text-on-primary hover:text-primary-container transition-colors" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">

                <form class="row mb-30" action="{{ route('Classrooms.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="repeater">
                            <div data-repeater-list="List_Classes">
                                <div data-repeater-item>
                                    <div class="row mb-3">

                                        <div class="col-md-3 mb-3">
                                            <label for="Name"
                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_class') }}
                                                :</label>
                                            <input class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" type="text" name="Name" />
                                        </div>


                                        <div class="col-md-3 mb-3">
                                            <label for="Name"
                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_class_en') }}
                                                :</label>
                                            <input class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" type="text" name="Name_class_en" />
                                        </div>


                                        <div class="col-md-3 mb-3">
                                            <label for="Name_en"
                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Name_Grade') }}
                                                :</label>

                                            <div class="box">
                                                <select class="fancyselect bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2" name="Grade_id">
                                                    @foreach ($Grades as $Grade)
                                                        <option value="{{ $Grade->id }}">{{ $Grade->Name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                        </div>

                                        <div class="col-md-3 mb-3">
                                            <label for="Name_en"
                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('My_Classes_trans.Processes') }}
                                                :</label>
                                            <input class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 w-full transition-all transform hover:scale-105" data-repeater-delete
                                                type="button" value="{{ trans('My_Classes_trans.delete_row') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <input class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" data-repeater-create type="button" value="{{ trans('My_Classes_trans.add_row') }}"/>
                                </div>

                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                    data-dismiss="modal">{{ trans('Grades_trans.Close') }}</button>
                                <button type="submit"
                                    class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Grades_trans.submit') }}</button>
                            </div>


                        </div>
                    </div>
                </form>
            </div>


        </div>

    </div>
</div>


<!-- حذف مجموعة صفوف -->
<div class="modal fade" id="delete_all" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
            <div class="modal-header bg-error text-on-error rounded-t-xl border-0">
                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0" id="exampleModalLabel">
                    {{ trans('My_Classes_trans.delete_class') }}
                </h5>
                <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form action="{{ route('delete_all') }}" method="POST">
                {{ csrf_field() }}
                <div class="modal-body p-4">
                    <p class="text-body-md text-on-surface mb-4">{{ trans('My_Classes_trans.Warning_Grade') }}</p>
                    <input class="text" type="hidden" id="delete_all_id" name="delete_all_id" value=''>
                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                            data-dismiss="modal">{{ trans('My_Classes_trans.Close') }}</button>
                    <button type="submit" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('My_Classes_trans.submit') }}</button>
                </div>
            </form>
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

<script type="text/javascript">
    $(function() {
        $("#btn_delete_all").click(function() {
            var selected = new Array();
            $("#datatable input[type=checkbox]:checked").each(function() {
                selected.push(this.value);
            });

            if (selected.length > 0) {
                $('#delete_all').modal('show')
                $('input[id="delete_all_id"]').val(selected);
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

.col-xl-12 {
    border-radius: 12px;
}

.col-md-3, .col-md-6 {
    border-radius: 8px;
}

.form-group {
    border-radius: 8px;
}

.box {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .table, .btn, .modal-content, .modal-header, .modal-footer, .form-control, select {
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
