@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    {{ trans('Grades_trans.title_page') }}
@stop
@endsection
@section('page-header')
<!-- breadcrumb -->
@section('PageTitle')
{{ trans('main_trans.Grades') }}
@stop
<!-- breadcrumb -->
@endsection
@section('content')
<!-- row -->
<div class="row p-4">
    <div class="col-xl-12 mb-30">
        <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
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

                <button type="button" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105" data-toggle="modal" data-target="#exampleModal">
                    {{ trans('Grades_trans.add_Grade') }}
                </button>
                <br><br>

                <div class="table-responsive rounded-lg">
                    <table id="datatable" class="table table-hover rounded-lg" data-page-length="50"
                        style="text-align: center">
                        <thead>
                            <tr class="bg-surface-container">
                                <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg">#</th>
                                <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Grades_trans.Name') }}</th>
                                <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Grades_trans.Notes') }}</th>
                                <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">{{ trans('Grades_trans.Processes') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-outline-variant">
                            <?php $i = 0; ?>
                            @foreach ($Grades as $Grade)
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
                                                {{mb_substr($Grade->Name, 0, 2)}}
                                            </div>
                                            <span class="text-body-md">{{$Grade->Name}}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$Grade->Notes}}</td>
                                    <td class="px-4 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button type="button" class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" data-toggle="modal"
                                                data-target="#edit{{ $Grade->id }}"
                                                title="{{ trans('Grades_trans.Edit') }}"><i class="fa fa-edit"></i></button>
                                            <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" data-toggle="modal"
                                                data-target="#delete{{ $Grade->id }}"
                                                title="{{ trans('Grades_trans.Delete') }}"><i
                                                    class="fa fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>

                                <!-- edit_modal_Grade -->
                                <div class="modal fade" id="edit{{ $Grade->id }}" tabindex="-1" role="dialog"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                            <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                                                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0"
                                                    id="exampleModalLabel">
                                                    {{ trans('Grades_trans.edit_Grade') }}
                                                </h5>
                                                <button type="button" class="close text-on-primary hover:text-primary-container transition-colors" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <!-- add_form -->
                                                <form action="{{ route('Grades.update', 'test') }}" method="post">
                                                    {{ method_field('patch') }}
                                                    @csrf
                                                    <div class="row mb-3">
                                                        <div class="col-md-6 mb-3">
                                                            <label for="Name"
                                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.stage_name_ar') }}
                                                                :</label>
                                                            <input id="Name" type="text" name="Name"
                                                                class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                                value="{{ $Grade->getTranslation('Name', 'ar') }}"
                                                                required>
                                                            <input id="id" type="hidden" name="id" class="form-control"
                                                                value="{{ $Grade->id }}">
                                                        </div>
                                                        <div class="col-md-6 mb-3">
                                                            <label for="Name_en"
                                                                class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.stage_name_en') }}
                                                                :</label>
                                                            <input type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg"
                                                                value="{{ $Grade->getTranslation('Name', 'en') }}"
                                                                name="Name_en" required>
                                                        </div>
                                                    </div>
                                                    <div class="form-group mb-4">
                                                        <label
                                                            for="exampleFormControlTextarea1" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.Notes') }}
                                                            :</label>
                                                        <textarea class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="Notes"
                                                            id="exampleFormControlTextarea1"
                                                            rows="3">{{ $Grade->Notes }}</textarea>
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
                                <div class="modal fade" id="delete{{ $Grade->id }}" tabindex="-1" role="dialog"
                                    aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog" role="document">
                                        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
                                            <div class="modal-header bg-error text-on-error rounded-t-xl border-0">
                                                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0"
                                                    id="exampleModalLabel">
                                                    {{ trans('Grades_trans.delete_Grade') }}
                                                </h5>
                                                <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="modal"
                                                    aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body p-4">
                                                <form action="{{ route('Grades.destroy', 'test') }}" method="post">
                                                    {{ method_field('Delete') }}
                                                    @csrf
                                                    <p class="text-body-md text-on-surface mb-4">{{ trans('Grades_trans.Warning_Grade') }}</p>
                                                    <input id="id" type="hidden" name="id" class="form-control"
                                                        value="{{ $Grade->id }}">
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                                                            data-dismiss="modal">{{ trans('Grades_trans.Close') }}</button>
                                                        <button type="submit"
                                                            class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Grades_trans.submit') }}</button>
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


<!-- add_modal_Grade -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content bg-surface-container-lowest rounded-xl border-0 shadow-xl">
            <div class="modal-header bg-primary text-on-primary rounded-t-xl border-0">
                <h5 style="font-family: 'Cairo', sans-serif;" class="modal-title text-title-lg mb-0" id="exampleModalLabel">
                    {{ trans('Grades_trans.add_Grade') }}
                </h5>
                <button type="button" class="close text-on-primary hover:text-primary-container transition-colors" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <!-- add_form -->
                <form action="{{ route('Grades.store') }}" method="POST">
                    @csrf
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label for="Name" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.stage_name_ar') }}
                                :</label>
                            <input id="Name" type="text" name="Name" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="Name_en" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.stage_name_en') }}
                                :</label>
                            <input type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="Name_en">
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="exampleFormControlTextarea1" class="text-label-lg text-on-surface-variant mb-2">{{ trans('Grades_trans.Notes') }}
                            :</label>
                        <textarea class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="Notes" id="exampleFormControlTextarea1"
                            rows="3"></textarea>
                    </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn bg-surface-variant text-on-surface-variant hover:bg-surface-container rounded-lg px-4 py-2 transition-all"
                    data-dismiss="modal">{{ trans('Grades_trans.Close') }}</button>
                <button type="submit" class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-4 py-2 transition-all transform hover:scale-105">{{ trans('Grades_trans.submit') }}</button>
            </div>
            </form>

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

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-xl-12 {
    border-radius: 12px;
}

.col-md-6 {
    border-radius: 8px;
}

.form-group {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .table, .btn, .alert, .modal-content, .modal-header, .modal-footer, .form-control {
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
