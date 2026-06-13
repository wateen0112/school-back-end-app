<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="School Management System" />
    <meta name="description" content="School Management System - Modern Educational Platform" />
    <meta name="author" content="School Management" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    @include('layouts.head')
</head>

<body class="bg-background text-on-background">

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

          @yield('page-header')
<div class="page-header bg-surface-container border-b border-outline-variant rounded-lg mb-4 p-4">
    <div class="row">
        <div class="col-sm-6">
            <h4 class="page-title mb-0 text-title-lg text-primary">@yield('PageTitle')</h4>
        </div>
        <div class="col-sm-6">
            <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right bg-transparent mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/dashboard') }}" class="text-primary hover:text-primary-container transition-colors">{{trans('main_trans.Dashboard')}}</a></li>
                <li class="breadcrumb-item active text-on-surface-variant">@yield('PageTitle')</li>
            </ol>
        </div>
    </div>

            @yield('content')

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

<style>
/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.wrapper {
    border-radius: 12px;
}

.content-wrapper {
    border-radius: 12px;
}

.page-header {
    border-radius: 12px !important;
}

.row {
    border-radius: 8px;
}

.col-sm-6, .col-sm-4, .col-sm-8, .col-sm-12 {
    border-radius: 8px;
}

.breadcrumb {
    border-radius: 6px;
}

.breadcrumb-item {
    border-radius: 4px;
}

/* Enhanced styling */
.page-title {
    border-radius: 6px;
}

/* Smooth transitions */
div, .page-header, .breadcrumb, .breadcrumb-item {
    transition: border-radius 0.3s ease;
}

/* Hover effects */
.breadcrumb-item:hover {
    transform: scale(1.05);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    div {
        border-radius: 6px;
    }
    
    .wrapper {
        border-radius: 8px;
    }
    
    .content-wrapper {
        border-radius: 8px;
    }
    
    .page-header {
        border-radius: 8px !important;
    }
}
</style>
