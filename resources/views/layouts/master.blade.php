<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="YallaSchool - School Management System" />
    <meta name="description" content="YallaSchool - Modern School Management Platform" />
    <meta name="author" content="YallaSchool" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&family=Tajawal:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @include(layouts.head)
</head>

<body class="bg-background text-on-background">

    <div class="wrapper" style="font-family: Cairo, Tajawal, sans-serif">

        <!-- Preloader -->
        <div id="pre-loader">
            <img src="{{ URL::asset(assets/images/pre-loader/loader-01.svg) }}" alt="">
        </div>

        @include(layouts.main-header)
        @include(layouts.main-sidebar)

        <!-- Main content -->
        <div class="content-wrapper">

            @yield(page-header)

            <div class="page-header bg-surface border-b border-outline" style="border-radius: 12px; margin-bottom: 24px; padding: 20px 24px;">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="page-title mb-0 text-title-lg" style="color: #20263a; font-weight: 800;">@yield(PageTitle)</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right bg-transparent mb-0">
                            <li class="breadcrumb-item">
                                <a href="{{ url(/dashboard) }}" style="color: #5f7cf7; text-decoration: none; font-weight: 600;">
                                    {{ trans(main_trans.Dashboard) }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active" style="color: #7b86a6;">@yield(PageTitle)</li>
                        </ol>
                    </div>
                </div>
            </div>

            @yield(content)

            @include(layouts.footer)
        </div>
    </div>

    @include(layouts.footer-scripts)

</body>

</html>

<style>
/* Fade in animation */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(16px); }
    to { opacity: 1; transform: translateY(0); }
}
.fade-in {
    animation: fadeInUp 0.5s ease-out forwards;
    opacity: 0;
}
.fade-in:nth-child(1) { animation-delay: 0s; }
.fade-in:nth-child(2) { animation-delay: 0.1s; }
.fade-in:nth-child(3) { animation-delay: 0.2s; }
.fade-in:nth-child(4) { animation-delay: 0.3s; }

/* Smooth card hover */
.card-statistics {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.card-statistics:hover {
    transform: translateY(-3px);
}

/* Global border radius */
.page-header { border-radius: 12px !important; }
</style>
