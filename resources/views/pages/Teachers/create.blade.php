@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    {{ trans('Teacher_trans.Add_Teacher') }}
@stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
@section('PageTitle')
    {{ trans('Teacher_trans.Add_Teacher') }}
@stop
<!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->
    <div class="row p-4">
        <div class="col-md-12 mb-30">
            <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-body">

                    @if(session()->has('error'))
                        <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
                            <strong>{{ session()->get('error') }}</strong>
                            <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <div class="col-xs-12">
                        <div class="col-md-12">
                            <br>
                            <form action="{{route('Teachers.store')}}" method="post">
                             @csrf
                            <div class="form-row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Email')}}</label>
                                    <input type="email" name="Email" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                    @error('Email')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Password')}}</label>
                                    <input type="password" name="Password" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                    @error('Password')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>


                            <div class="form-row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Name_ar')}}</label>
                                    <input type="text" name="Name_ar" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                    @error('Name_ar')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Name_en')}}</label>
                                    <input type="text" name="Name_en" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                    @error('Name_en')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-row mb-4">
                                <div class="form-group col-md-6 mb-3">
                                    <label for="inputCity" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.specialization')}}</label>
                                    <select class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2" name="Specialization_id">
                                        <option selected disabled>{{trans('Parent_trans.Choose')}}...</option>
                                        @foreach($specializations as $specialization)
                                            <option value="{{$specialization->id}}">{{$specialization->Name}}</option>
                                        @endforeach
                                    </select>
                                    @error('Specialization_id')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="form-group col-md-6 mb-3">
                                    <label for="inputState" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Gender')}}</label>
                                    <select class="custom-select bg-surface-container border-outline-variant text-on-surface rounded-lg px-3 py-2" name="Gender_id">
                                        <option selected disabled>{{trans('Parent_trans.Choose')}}...</option>
                                        @foreach($genders as $gender)
                                            <option value="{{$gender->id}}">{{$gender->Name}}</option>
                                        @endforeach
                                    </select>
                                    @error('Gender_id')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Joining_Date')}}</label>
                                    <div class='input-group date'>
                                        <input class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" type="text"  id="datepicker-action" name="Joining_Date" data-date-format="yyyy-mm-dd"  required>
                                    </div>
                                    @error('Joining_Date')
                                    <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="exampleFormControlTextarea1" class="text-label-lg text-on-surface-variant mb-2">{{trans('Teacher_trans.Address')}}</label>
                                <textarea class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="Address"
                                          id="exampleFormControlTextarea1" rows="4"></textarea>
                                @error('Address')
                                <div class="alert bg-error-container text-on-error border-0 rounded-lg mt-2">{{ $message }}</div>
                                @enderror
                            </div>

                            <button class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-6 py-3 transition-all transform hover:scale-105 pull-right" type="submit">{{trans('Parent_trans.Next')}}</button>
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

.form-control {
    border-radius: 8px !important;
}

.btn {
    border-radius: 8px !important;
}

.alert {
    border-radius: 8px !important;
}

select {
    border-radius: 8px !important;
}

textarea {
    border-radius: 8px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-12, .col-xs-12, .form-row {
    border-radius: 10px;
}

.col-md-6, .col, .form-group {
    border-radius: 8px;
}

.input-group {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .form-control, .btn, .alert, select, textarea, .input-group {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

.form-control:focus {
    transform: scale(1.02);
}

select:focus {
    transform: scale(1.02);
}

textarea:focus {
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
    
    .form-control {
        border-radius: 6px !important;
    }
    
    .btn {
        border-radius: 6px !important;
    }
    
    .alert {
        border-radius: 6px !important;
    }
    
    select {
        border-radius: 6px !important;
    }
    
    textarea {
        border-radius: 6px !important;
    }
}
</style>
@endpush
