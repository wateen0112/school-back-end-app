<!--=================================
header start-->
<nav class="admin-header navbar navbar-default col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
    <!-- logo -->
    <div class="text-left navbar-brand-wrapper">
        <a class="navbar-brand brand-logo" href="{{ url('/dashboard') }}">
            <span style="font-family: 'Cairo', sans-serif; font-weight: 900; font-size: 20px; color: #5f7cf7;">
                <i class="fas fa-graduation-cap mr-1" style="color: #ff704d;"></i>يلا سكول
            </span>
        </a>
        <a class="navbar-brand brand-logo-mini" href="{{ url('/dashboard') }}">
            <span style="font-family: 'Cairo', sans-serif; font-weight: 900; font-size: 22px; color: #5f7cf7;">ي</span>
        </a>
    </div>

    <!-- Top bar left -->
    <ul class="nav navbar-nav mr-auto">
        <li class="nav-item">
            <a id="button-toggle" class="button-toggle-nav inline-block ml-20 pull-left" href="javascript:void(0);">
                <i class="zmdi zmdi-menu ti-align-right"></i>
            </a>
        </li>
        <li class="nav-item">
            <div class="search">
                <a class="search-btn not_click" href="javascript:void(0);"></a>
                <div class="search-box not-click">
                    <input type="text" class="not-click form-control" placeholder="بحث..." value="" name="search">
                    <button class="search-button" type="submit"><i class="fa fa-search not-click"></i></button>
                </div>
            </div>
        </li>
    </ul>

    <!-- top bar right -->
    <ul class="nav navbar-nav ml-auto">

        <!-- Language Switcher -->
        <div class="btn-group mb-1">
            <button type="button" class="btn btn-light btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                @if (App::getLocale() == 'ar')
                    العربية <img src="{{ URL::asset('assets/images/flags/EG.png') }}" alt="" style="margin-right: 6px;">
                @else
                    English <img src="{{ URL::asset('assets/images/flags/US.png') }}" alt="" style="margin-right: 6px;">
                @endif
            </button>
            <div class="dropdown-menu">
                @foreach(LaravelLocalization::getSupportedLocales() as $localeCode => $properties)
                    <a class="dropdown-item" rel="alternate" hreflang="{{ $localeCode }}" href="{{ LaravelLocalization::getLocalizedURL($localeCode, null, [], true) }}">
                        {{ $properties['native'] }}
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Fullscreen -->
        <li class="nav-item fullscreen">
            <a id="btnFullscreen" href="#" class="nav-link" title="ملء الشاشة"><i class="ti-fullscreen"></i></a>
        </li>

        <!-- Notifications -->
        <li class="nav-item dropdown">
            <a class="nav-link top-nav" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <i class="ti-bell"></i>
                <span class="badge badge-danger notification-status"></span>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-big dropdown-notifications">
                <div class="dropdown-header notifications">
                    <strong>الإشعارات</strong>
                    <span class="badge badge-pill badge-warning">05</span>
                </div>
                <div class="dropdown-divider"></div>
                <a href="#" class="dropdown-item">طالب جديد مسجل <small class="float-left text-muted time">الآن</small></a>
                <a href="#" class="dropdown-item">فاتورة جديدة مستلمة <small class="float-left text-muted time">22 دقيقة</small></a>
                <a href="#" class="dropdown-item">تقرير خطأ في الخادم <small class="float-left text-muted time">7 ساعات</small></a>
                <a href="#" class="dropdown-item">تقرير قاعدة البيانات <small class="float-left text-muted time">1 يوم</small></a>
                <a href="#" class="dropdown-item">تأكيد طلب <small class="float-left text-muted time">2 يوم</small></a>
            </div>
        </li>

        <!-- Quick Links -->
        <li class="nav-item dropdown">
            <a class="nav-link top-nav" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="true">
                <i class="ti-view-grid"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right dropdown-big">
                <div class="dropdown-header">
                    <strong>روابط سريعة</strong>
                </div>
                <div class="dropdown-divider"></div>
                <div class="nav-grid">
                    <a href="{{ route('Students.create') }}" class="nav-grid-item">
                        <i class="fas fa-user-plus text-primary"></i>
                        <h5>إضافة طالب</h5>
                    </a>
                    <a href="{{ route('Teachers.create') }}" class="nav-grid-item">
                        <i class="fas fa-chalkboard-teacher text-success"></i>
                        <h5>إضافة معلم</h5>
                    </a>
                </div>
                <div class="nav-grid">
                    <a href="{{ route('Fees.index') }}" class="nav-grid-item">
                        <i class="fas fa-money-bill-wave text-warning"></i>
                        <h5>الرسوم</h5>
                    </a>
                    <a href="{{ route('Attendance.index') }}" class="nav-grid-item">
                        <i class="fas fa-calendar-check text-danger"></i>
                        <h5>الحضور</h5>
                    </a>
                </div>
            </div>
        </li>

        <!-- User Profile -->
        <li class="nav-item dropdown mr-30">
            <a class="nav-link nav-pill user-avatar" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false">
                <img src="{{ URL::asset('assets/images/user_icon.png') }}" alt="avatar">
            </a>
            <div class="dropdown-menu dropdown-menu-right">
                <div class="dropdown-header">
                    <div class="media">
                        <div class="media-body">
                            <h5 class="mt-0 mb-0">{{ Auth::user()->name ?? 'Admin' }}</h5>
                            <span>{{ Auth::user()->email ?? 'admin@school.local' }}</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#"><i class="text-secondary ti-reload"></i> النشاط</a>
                <a class="dropdown-item" href="#"><i class="text-success ti-email"></i> الرسائل</a>
                <a class="dropdown-item" href="#"><i class="text-warning ti-user"></i> الملف الشخصي</a>
                <a class="dropdown-item" href="#"><i class="text-info ti-layers-alt"></i> المشاريع <span class="badge badge-info">6</span></a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item" href="#"><i class="text-primary ti-settings"></i> الإعدادات</a>

                @if(auth('student')->check())
                    <form method="GET" action="{{ route('logout','student') }}">
                @elseif(auth('teacher')->check())
                    <form method="GET" action="{{ route('logout','teacher') }}">
                @elseif(auth('parent')->check())
                    <form method="GET" action="{{ route('logout','parent') }}">
                @else
                    <form method="GET" action="{{ route('logout','web') }}">
                @endif
                    @csrf
                    <a class="dropdown-item" href="#" onclick="event.preventDefault();this.closest('form').submit();">
                        <i class="bx bx-log-out text-danger"></i> تسجيل الخروج
                    </a>
                </form>
            </div>
        </li>
    </ul>
</nav>
<!--=================================
header End-->
