@extends('layouts.master')
@section('css')
    @toastr_css
@section('title')
    الاعدادات
@stop
@endsection
@section('page-header')
<!-- breadcrumb -->
@section('PageTitle')
    الاعدادات
@stop
<!-- breadcrumb -->
@endsection
@section('content')


    @if(session()->has('error'))
        <div class="alert bg-error-container text-on-error border-0 rounded-lg mb-4">
            <strong>{{ session()->get('error') }}</strong>
            <button type="button" class="close text-on-error hover:text-error-container transition-colors" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif


<!-- row -->
<div class="row p-4">
    <div class="col-md-12 mb-30">
        <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
            <div class="card-body">
                <form enctype="multipart/form-data" method="post" action="{{route('settings.update','test')}}">
                    @csrf @method('PUT')
                    <div class="row">
                        <div class="col-md-6 border-r-4 border-r-outline-variant pr-6">
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">اسم المدرسة<span class="text-error">*</span></label>
                                <div class="col-lg-8">
                                    <input name="school_name" value="{{ $setting['school_name'] }}" required type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" placeholder="Name of School">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label for="current_session" class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">العام الحالي<span class="text-error">*</span></label>
                                <div class="col-lg-8">
                                    <select data-placeholder="Choose..." required name="current_session" id="current_session" class="select-search form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                        <option value=""></option>
                                        @for($y=date('Y', strtotime('- 3 years')); $y<=date('Y', strtotime('+ 1 years')); $y++)
                                            <option {{ ($setting['current_session'] == (($y-=1).'-'.($y+=1))) ? 'selected' : '' }}>{{ ($y-=1).'-'.($y+=1) }}</option>
                                        @endfor
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">اسم المدرسة المختصر</label>
                                <div class="col-lg-8">
                                    <input name="school_title" value="{{ $setting['school_title'] }}" type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" placeholder="School Acronym">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">الهاتف</label>
                                <div class="col-lg-8">
                                    <input name="phone" value="{{ $setting['phone'] }}" type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" placeholder="Phone">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">البريد الالكتروني</label>
                                <div class="col-lg-8">
                                    <input name="school_email" value="{{ $setting['school_email'] }}" type="email" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" placeholder="School Email">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">عنوان المدرسة<span class="text-error">*</span></label>
                                <div class="col-lg-8">
                                    <input required name="address" value="{{ $setting['address'] }}" type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" placeholder="School Address">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">نهاية الترم الاول </label>
                                <div class="col-lg-8">
                                    <input name="end_first_term" value="{{ $setting['end_first_term'] }}" type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg date-pick" placeholder="Date Term Ends">
                                </div>
                            </div>
                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">نهاية الترم الثاني</label>
                                <div class="col-lg-8">
                                    <input name="end_second_term" value="{{ $setting['end_second_term'] }}" type="text" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg date-pick" placeholder="Date Term Ends">
                                </div>
                            </div>

                            <div class="form-group row mb-4">
                                <label class="col-lg-3 col-form-label text-label-lg text-on-surface-variant font-weight-semibold">شعار المدرسة</label>
                                <div class="col-lg-8">
                                    <div class="mb-3">
                                        <img style="width: 100px" height="100px" class="rounded-lg shadow-md" src="{{ URL::asset('attachments/logo/'.$setting['logo']) }}" alt="">
                                    </div>
                                    <input name="logo" accept="image/*" type="file" class="file-input bg-surface-container border-outline-variant text-on-surface rounded-lg" data-show-caption="false" data-show-upload="false" data-fouc>
                                </div>
                            </div>
                        </div>
                    </div>
                    <hr class="border-outline-variant">
                    <button class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-6 py-3 transition-all transform hover:scale-105 pull-right" type="submit">{{trans('Students_trans.submit')}}</button>
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

input[type="file"] {
    border-radius: 8px !important;
}

img {
    border-radius: 8px;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-12, .col-md-6, .col-lg-3, .col-lg-8, .form-group {
    border-radius: 8px;
}

/* Border right styling */
.border-r-4 {
    border-right-width: 4px;
}

/* Smooth transitions */
div, .card, .card-body, .form-control, .btn, .alert, select, input[type="file"], img {
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

img:hover {
    transform: scale(1.05);
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
    
    input[type="file"] {
        border-radius: 6px !important;
    }
    
    img {
        border-radius: 6px;
    }
}
</style>
@endpush
