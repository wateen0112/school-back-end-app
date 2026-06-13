@extends('layouts.master')
@section('css')
    @toastr_css
    @section('title')
        الملف الشخصي
    @stop
@endsection
@section('page-header')
    <!-- breadcrumb -->
    @section('PageTitle')
        الملف الشخصي
    @stop
    <!-- breadcrumb -->
@endsection
@section('content')
    <!-- row -->

    <div class="card-body p-4">

        <section class="bg-surface-container">
            <div class="row">
                <div class="col-lg-4">
                    <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl mb-4">
                        <div class="card-body text-center p-4">
                            <img src="{{URL::asset('assets/images/teacher.png')}}"
                                 alt="avatar"
                                 class="rounded-circle img-fluid shadow-lg" style="width: 150px;">
                            <h5 style="font-family: Cairo" class="my-3 text-title-lg text-on-surface">{{$information->Name}}</h5>
                            <p class="text-on-surface-variant mb-1">{{$information->email}}</p>
                            <p class="bg-primary/15 text-primary px-3 py-1 rounded-full text-sm d-inline-block">ولي امر</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl mb-4">
                        <div class="card-body p-4">
                            <form action="{{route('profile.update.parent',$information->id)}}" method="post">
                                @csrf
                                <div class="row mb-4">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-label-lg text-on-surface-variant">اسم المستخدم باللغة العربية</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="text-on-surface mb-0">
                                            <input type="text" name="Name_ar"
                                                   value="{{ $information->getTranslation('Name_Father', 'ar') }}"
                                                   class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                        </p>
                                    </div>
                                </div>
                                <hr class="border-outline-variant">
                                <div class="row mb-4">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-label-lg text-on-surface-variant">اسم المستخدم باللغة الانجليزية</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="text-on-surface mb-0">
                                            <input type="text" name="Name_en"
                                                   value="{{ $information->getTranslation('Name_Father', 'en') }}"
                                                   class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg">
                                        </p>
                                    </div>
                                </div>
                                <hr class="border-outline-variant">
                                <div class="row mb-4">
                                    <div class="col-sm-3">
                                        <p class="mb-0 text-label-lg text-on-surface-variant">كلمة المرور</p>
                                    </div>
                                    <div class="col-sm-9">
                                        <p class="text-on-surface mb-0">
                                            <input type="password" id="password" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="password">
                                        </p><br><br>
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input rounded" onclick="myFunction()"
                                                   id="exampleCheck1">
                                            <label class="form-check-label text-label-md text-on-surface-variant" for="exampleCheck1">اظهار كلمة المرور</label>
                                        </div>
                                    </div>
                                </div>
                                <hr class="border-outline-variant">
                                <button type="submit" class="btn bg-success text-on-success hover:bg-success-container rounded-lg px-6 py-3 transition-all transform hover:scale-105">تعديل البيانات</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- row closed -->
@endsection
@section('js')
    @toastr_js
    @toastr_render
    <script>
        function myFunction() {
            var x = document.getElementById("password");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
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

.form-control {
    border-radius: 8px !important;
}

.btn {
    border-radius: 8px !important;
}

.rounded-circle {
    border-radius: 50% !important;
}

.form-check-input {
    border-radius: 4px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-lg-4, .col-lg-8, .col-sm-3, .col-sm-9 {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .form-control, .btn, .rounded-circle, .form-check-input {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

.form-control:focus {
    transform: scale(1.02);
}

.card:hover {
    transform: translateY(-2px);
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
}
</style>
@endpush

