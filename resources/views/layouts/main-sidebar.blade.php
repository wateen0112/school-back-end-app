<div class="container-fluid">
    <div class="row">
        <!-- Sidebar start-->
        <div class="side-menu-fixed" style="{{ App::getLocale() == 'ar' ? 'right: 0; left: auto;' : 'left: 0; right: auto;' }}">

            @if (auth('web')->check())
                @include('layouts.main-sidebar.admin-main-sidebar')
            @endif

            @if (auth('student')->check())
                @include('layouts.main-sidebar.student-main-sidebar')
            @endif

            @if (auth('teacher')->check())
                @include('layouts.main-sidebar.teacher-main-sidebar')
            @endif

            @if (auth('parent')->check())
                @include('layouts.main-sidebar.parent-main-sidebar')
            @endif

        </div>

        <!-- Sidebar End-->

        <!--=================================
