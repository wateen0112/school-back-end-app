<!DOCTYPE html>
<html lang="en" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="HTML5 Template" />
    <meta name="description" content="Webmin - Bootstrap 4 & Angular 5 Admin Dashboard Template" />
    <meta name="author" content="potenzaglobalsolutions.com" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title>برنامج مورا سوفت لادارة المدارس</title>

    <!-- Favicon -->
    <link rel="shortcut icon" href="images/favicon.ico" />

    <!-- Font -->
    <link rel="stylesheet"
          href="https://fonts.googleapis.com/css?family=Cairo:200,300,300i,400,400i,500,500i,600,600i,700,700i,800,800i,900">

    <!-- css -->
    <link href="{{ URL::asset('assets/css/rtl.css') }}" rel="stylesheet">

</head>

<body class="bg-background text-on-background">

<div class="wrapper">
    <!--=================================
preloader -->

    <div id="pre-loader">
        <img src="{{URL::asset('assets/images/pre-loader/loader-01.svg')}}" alt="">
    </div>

    <!--=================================
preloader -->

    <!--=================================
login-->

    <section class="height-100vh d-flex align-items-center page-section-ptb login"
             style="background-image: url('{{ asset('assets/images/sativa.png')}}');">
        <div class="container">
            <div class="row justify-content-center no-gutters vertical-align">
                <div class="col-lg-4 col-md-6 login-fancy-bg bg-primary rounded-xl shadow-xl"
                     style="background-image: url('{{ asset('assets/images/login-inner-bg.jpg')}}');">
                    <div class="login-fancy">
                        <h2 class="text-on-primary mb-20 text-title-lg">مرحباً بالعالم!</h2>
                        <p class="mb-20 text-on-primary/80">أنشئ مواقع ويب مخصصة مع القالب متعدد الأغراض حصريًا متجاوب مع ميزات قوية.</p>
                        <ul class="list-unstyled pos-bot pb-30">
                            <li class="list-inline-item"><a class="text-on-primary hover:text-primary-container transition-colors" href="#"> شروط الاستخدام</a> </li>
                            <li class="list-inline-item"><a class="text-on-primary hover:text-primary-container transition-colors" href="#"> سياسة الخصوصية</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 bg-surface-container-lowest rounded-xl shadow-xl">
                    <div class="login-fancy pb-40 clearfix">
                        @if($type == 'student')
                            <h3 style="font-family: 'Cairo', sans-serif" class="mb-30 text-title-lg text-primary">تسجيل دخول طالب</h3>
                        @elseif($type == 'parent')
                            <h3 style="font-family: 'Cairo', sans-serif" class="mb-30 text-title-lg text-primary">تسجيل دخول ولي امر</h3>
                        @elseif($type == 'teacher')
                            <h3 style="font-family: 'Cairo', sans-serif" class="mb-30 text-title-lg text-primary">تسجيل دخول معلم</h3>
                        @else
                            <h3 style="font-family: 'Cairo', sans-serif" class="mb-30 text-title-lg text-primary">تسجيل دخول ادمن</h3>
                        @endif

                            @if (\Session::has('message'))
                                <div class="alert alert-danger bg-error-container text-on-error border-0 rounded-lg mb-20">
                                 <li>{!! \Session::get('message') !!}</li>
                                </div>
                            @endif


                        <form method="POST" action="{{route('login')}}">
                            @csrf

                            <div class="section-field mb-20">
                                <label class="mb-10 text-label-lg text-on-surface-variant" for="name">البريدالالكتروني*</label>
                                <input id="email" type="email"
                                       class="form-control @error('email') is-invalid @enderror bg-surface-container border-outline-variant text-on-surface rounded-lg" name="email"
                                       value="{{ old('email') }}" required autocomplete="email" autofocus>
                                <input type="hidden" value="{{$type}}" name="type">
                                @error('email')
                                <span class="invalid-feedback text-error" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                @enderror

                            </div>

                            <div class="section-field mb-20">
                                <label class="mb-10 text-label-lg text-on-surface-variant" for="Password">كلمة المرور * </label>
                                <input id="password" type="password"
                                       class="form-control @error('password') is-invalid @enderror bg-surface-container border-outline-variant text-on-surface rounded-lg" name="password"
                                       required autocomplete="current-password">

                                @error('password')
                                <span class="invalid-feedback text-error" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                @enderror

                            </div>
                            <div class="section-field">
                                <div class="remember-checkbox mb-30">
                                    <input type="checkbox" class="form-control rounded" name="two" id="two" />
                                    <label for="two" class="text-body-md text-on-surface"> تذكرني</label>
                                    <a href="#" class="float-right text-primary hover:text-primary-container transition-colors">هل نسيت كلمةالمرور ؟</a>
                                </div>
                            </div>
                            <button class="button bg-primary text-on-primary hover:bg-primary-container rounded-lg px-6 py-3 transition-all transform hover:scale-105"><span>دخول</span><i class="fa fa-check ml-2"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--=================================
login-->

</div>
<!-- jquery -->
<script src="{{ URL::asset('assets/js/jquery-3.3.1.min.js') }}"></script>
<!-- plugins-jquery -->
<script src="{{ URL::asset('assets/js/plugins-jquery.js') }}"></script>
<!-- plugin_path -->
<script>
    var plugin_path = 'js/';

</script>

<!-- chart -->
<script src="{{ URL::asset('assets/js/chart-init.js') }}"></script>
<!-- calendar -->
<script src="{{ URL::asset('assets/js/calendar.init.js') }}"></script>
<!-- charts sparkline -->
<script src="{{ URL::asset('assets/js/sparkline.init.js') }}"></script>
<!-- charts morris -->
<script src="{{ URL::asset('assets/js/morris.init.js') }}"></script>
<!-- datepicker -->
<script src="{{ URL::asset('assets/js/datepicker.js') }}"></script>
<!-- sweetalert2 -->
<script src="{{ URL::asset('assets/js/sweetalert2.js') }}"></script>
<!-- toastr -->
@yield('js')
<script src="{{ URL::asset('assets/js/toastr.js') }}"></script>
<!-- validation -->
<script src="{{ URL::asset('assets/js/validation.js') }}"></script>
<!-- lobilist -->
<script src="{{ URL::asset('assets/js/lobilist.js') }}"></script>
<!-- custom -->
<script src="{{ URL::asset('assets/js/custom.js') }}"></script>

<style>
/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.login-fancy {
    border-radius: 12px !important;
}

.section-field {
    border-radius: 8px;
}

.form-control {
    border-radius: 8px !important;
}

.button {
    border-radius: 8px !important;
}

.alert {
    border-radius: 8px !important;
}

.remember-checkbox {
    border-radius: 6px;
}

/* Enhanced styling */
.login-fancy-bg {
    border-radius: 16px !important;
}

.container {
    border-radius: 12px;
}

.row {
    border-radius: 12px;
}

/* Smooth transitions */
div, .form-control, .button, .alert {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.button:hover {
    transform: translateY(-2px);
}

.form-control:focus {
    transform: scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    div {
        border-radius: 6px;
    }
    
    .login-fancy {
        border-radius: 10px !important;
    }
    
    .form-control {
        border-radius: 6px !important;
    }
}
</style>

</body>

</html>
