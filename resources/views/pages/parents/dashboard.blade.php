<!DOCTYPE html>
<html lang="en">
@section('title')
{{trans('main_trans.Main_title')}}
@stop
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="HTML5 Template" />
    <meta name="description" content="Webmin - Bootstrap 4 & Angular 5 Admin Dashboard Template" />
    <meta name="author" content="potenzaglobalsolutions.com" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600&display=swap" rel="stylesheet">
    @include('layouts.head')
</head>

<body style="font-family: 'Cairo', sans-serif">

    <div class="wrapper" style="font-family: 'Cairo', sans-serif">

        <!--=================================
 preloader -->

 <div id="pre-loader">
     <img src="{{ URL::asset('assets/images/pre-loader/loader-01.svg') }}" alt="">
 </div>

        <!--=================================
 preloader -->

        @include('layouts.main-header')

        @include('layouts.main-sidebar')

        <!--=================================
 Main content -->
        <!-- main-content -->
        <div class="content-wrapper">
            <div class="page-title p-4" >
                <div class="row">
                    <div class="col-sm-6" >
                        <h4 class="mb-0 text-title-lg text-on-surface" style="font-family: 'Cairo', sans-serif">مرحبا بك : {{auth()->user()->Name_Father}}</h4>
                    </div><br><br>
                    <div class="col-sm-6">
                        <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right">
                        </ol>
                    </div>
                </div>
            </div>

            <section class="bg-surface-container">
                <div class="container py-5">
                    <div class="row justify-content-center">
                         @foreach($sons as $son)
                            <div class="col-md-8 col-lg-6 col-xl-4 mb-4">
                                <a href="" class="text-decoration-none">
                                    <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl hover:shadow-xl transition-all transform hover:scale-105">
                                        <img src="{{URL::asset('assets/images/my_son.png')}}" class="card-img-top rounded-t-xl"/>
                                        <div class="card-body p-4">
                                            <div class="text-center">
                                                <h5 style="font-family: 'Cairo', sans-serif"
                                                    class="card-title text-title-lg text-on-surface mb-3">{{$son->name}}</h5>
                                                <p class="text-on-surface-variant mb-4">معلومات الطالب</p>
                                            </div>
                                            <div class="space-y-3">
                                                <div class="d-flex justify-content-between align-items-center p-2 bg-surface-container rounded-lg">
                                                    <span class="text-label-md text-on-surface-variant">المرحلة</span>
                                                    <span class="bg-primary/15 text-primary px-2 py-1 rounded text-sm">{{$son->grade->Name}}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center p-2 bg-surface-container rounded-lg">
                                                    <span class="text-label-md text-on-surface-variant">الصف</span>
                                                    <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-sm">{{$son->classroom->Name_Class}}</span>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center p-2 bg-surface-container rounded-lg">
                                                    <span class="text-label-md text-on-surface-variant">القسم</span>
                                                    <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-sm">{{$son->section->Name_Section}}</span>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center p-2 bg-surface-container rounded-lg">
{{--                                                    @if(\App\Models\Degree::where('student_id',$son->id)->count() == 0)--}}
{{--                                                        <span>عدد الاختبارات</span><span--}}
{{--                                                            class="text-danger">{{\App\Models\Degree::where('student_id',$son->id)->count()}}</span>--}}
{{--                                                    @else--}}
{{--                                                        <span>عدد الاختبارات</span><span--}}
{{--                                                            class="text-success">{{\App\Models\Degree::where('student_id',$son->id)->count()}}</span>--}}
{{--                                                    @endif--}}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>




            <!--=================================
 wrapper -->

            <!--=================================
 footer -->

            @include('layouts.footer')
        </div><!-- main content wrapper end-->
    </div>
    </div>
    </div>
    <!--=================================
 footer -->

    @include('layouts.footer-scripts')
</body>

</html>

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

.card-img-top {
    border-radius: 16px 16px 0 0 !important;
}

.btn {
    border-radius: 8px !important;
}

/* Enhanced styling */
.row {
    border-radius: 12px;
}

.col-md-8, .col-lg-6, .col-xl-4 {
    border-radius: 8px;
}

/* Smooth transitions */
div, .card, .card-body, .card-img-top, .btn {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.card:hover {
    transform: translateY(-4px);
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
    
    .card-img-top {
        border-radius: 12px 12px 0 0 !important;
    }
    
    .btn {
        border-radius: 6px !important;
    }
}

/* Custom utility classes */
.space-y-3 > * + * {
    margin-top: 0.75rem;
}
</style>
@endpush
