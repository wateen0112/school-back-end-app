<!DOCTYPE html>
<html lang="ar" dir="rtl">
@section(title)
{{ trans(main_trans.Dashboard_page) }}
@stop
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="keywords" content="YallaSchool - School Management System" />
    <meta name="description" content="YallaSchool - Modern School Management Platform" />
    <meta name="author" content="YallaSchool" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @include(layouts.head)
    @livewireStyles
</head>

<body style="font-family: Cairo, sans-serif" class="bg-background text-on-background">

    <div class="wrapper" style="font-family: Cairo, sans-serif">

        <!-- Preloader -->
        <div id="pre-loader">
            <img src="{{ URL::asset(assets/images/pre-loader/loader-01.svg) }}" alt="">
        </div>

        @include(layouts.main-header)
        @include(layouts.main-sidebar)

        <!-- Main content -->
        <div class="content-wrapper">

            <!-- Page Title -->
            <div class="page-title bg-surface border-b border-outline" style="border-radius: 12px; margin-bottom: 24px; padding: 20px 24px;">
                <div class="row">
                    <div class="col-sm-6">
                        <h4 class="mb-0 text-title-lg" style="font-family: Cairo, sans-serif; color: #20263a; font-weight: 800;">
                            <i class="fas fa-chart-line mr-2" style="color: #5f7cf7;"></i>لوحة تحكم المدير
                        </h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right bg-transparent" style="margin: 0;">
                            <li class="breadcrumb-item">
                                <a href="{{ url(/dashboard) }}" style="color: #5f7cf7; text-decoration: none; font-weight: 600;">
                                    <i class="ti-home mr-1"></i>{{ trans(main_trans.Dashboard) }}
                                </a>
                            </li>
                            <li class="breadcrumb-item active" style="color: #7b86a6;">لوحة التحكم</li>
                        </ol>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row" style="padding: 0 12px;">

                <!-- Students Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 fade-in" style="border-radius: 16px; border: 1px solid #dfe5f5; box-shadow: 0 2px 12px rgba(95,124,247,0.08); animation-delay: 0s;">
                        <div class="card-body" style="padding: 24px;">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(95,124,247,0.12); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-graduate" style="font-size: 22px; color: #5f7cf7;"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p style="font-size: 13px; font-weight: 600; color: #7b86a6; margin-bottom: 4px;">عدد الطلاب</p>
                                    <h4 style="font-size: 28px; font-weight: 800; color: #5f7cf7; margin: 0;">{{ \App\Models\Student::count() }}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2" style="border-top: 1px solid #eef1f8; padding-top: 12px;">
                                <i class="fas fa-arrow-left mr-1" style="color: #5f7cf7;"></i>
                                <a href="{{ route(Students.index) }}" style="color: #5f7cf7; text-decoration: none; font-weight: 600;">عرض جميع الطلاب</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Teachers Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 fade-in" style="border-radius: 16px; border: 1px solid #dfe5f5; box-shadow: 0 2px 12px rgba(255,112,77,0.08); animation-delay: 0.1s;">
                        <div class="card-body" style="padding: 24px;">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(255,112,77,0.12); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-chalkboard-teacher" style="font-size: 22px; color: #ff704d;"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p style="font-size: 13px; font-weight: 600; color: #7b86a6; margin-bottom: 4px;">عدد المعلمين</p>
                                    <h4 style="font-size: 28px; font-weight: 800; color: #ff704d; margin: 0;">{{ \App\Models\Teacher::count() }}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2" style="border-top: 1px solid #eef1f8; padding-top: 12px;">
                                <i class="fas fa-arrow-left mr-1" style="color: #ff704d;"></i>
                                <a href="{{ route(Teachers.index) }}" style="color: #ff704d; text-decoration: none; font-weight: 600;">عرض جميع المعلمين</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Parents Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 fade-in" style="border-radius: 16px; border: 1px solid #dfe5f5; box-shadow: 0 2px 12px rgba(255,200,76,0.08); animation-delay: 0.2s;">
                        <div class="card-body" style="padding: 24px;">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(255,200,76,0.15); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-user-tie" style="font-size: 22px; color: #e6a800;"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p style="font-size: 13px; font-weight: 600; color: #7b86a6; margin-bottom: 4px;">أولياء الأمور</p>
                                    <h4 style="font-size: 28px; font-weight: 800; color: #e6a800; margin: 0;">{{ \App\Models\My_Parent::count() }}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2" style="border-top: 1px solid #eef1f8; padding-top: 12px;">
                                <i class="fas fa-arrow-left mr-1" style="color: #e6a800;"></i>
                                <a href="{{ url(add_parent) }}" style="color: #e6a800; text-decoration: none; font-weight: 600;">عرض أولياء الأمور</a>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Sections Card -->
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 fade-in" style="border-radius: 16px; border: 1px solid #dfe5f5; box-shadow: 0 2px 12px rgba(46,204,113,0.08); animation-delay: 0.3s;">
                        <div class="card-body" style="padding: 24px;">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div style="width: 56px; height: 56px; border-radius: 14px; background: rgba(46,204,113,0.12); display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-chalkboard" style="font-size: 22px; color: #2ecc71;"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p style="font-size: 13px; font-weight: 600; color: #7b86a6; margin-bottom: 4px;">الفصول الدراسية</p>
                                    <h4 style="font-size: 28px; font-weight: 800; color: #2ecc71; margin: 0;">{{ \App\Models\Section::count() }}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2" style="border-top: 1px solid #eef1f8; padding-top: 12px;">
                                <i class="fas fa-arrow-left mr-1" style="color: #2ecc71;"></i>
                                <a href="{{ route(Sections.index) }}" style="color: #2ecc71; text-decoration: none; font-weight: 600;">عرض الفصول</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Latest Activity -->
            <div class="row" style="padding: 0 12px;">
                <div class="col-xl-12 mb-30">
                    <div class="card h-100" style="border-radius: 16px; border: 1px solid #dfe5f5; box-shadow: 0 2px 12px rgba(95,124,247,0.06);">
                        <div class="card-body">
                            <div class="tab nav-border">
                                <div class="d-block d-md-flex justify-content-between align-items-center" style="border-bottom: 1px solid #eef1f8; padding-bottom: 16px; margin-bottom: 16px;">
                                    <div class="d-block w-100">
                                        <h5 style="font-family: Cairo, sans-serif; color: #20263a; font-weight: 700; margin: 0;">
                                            <i class="fas fa-clock-rotate-left mr-2" style="color: #5f7cf7;"></i>آخر العمليات على النظام
                                        </h5>
                                    </div>
                                    <div class="d-block d-md-flex nav-tabs-custom">
                                        <ul class="nav nav-tabs" style="border: none;" id="myTab" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active show" style="border: none; border-bottom: 2px solid #5f7cf7; color: #5f7cf7; font-weight: 700; background: transparent;" id="students-tab" data-toggle="tab" href="#students" role="tab" aria-controls="students" aria-selected="true">
                                                    <i class="fas fa-user-graduate mr-1"></i> الطلاب
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" style="border: none; border-bottom: 2px solid transparent; color: #7b86a6; font-weight: 600;" id="teachers-tab" data-toggle="tab" href="#teachers" role="tab" aria-controls="teachers" aria-selected="false">
                                                    <i class="fas fa-chalkboard-teacher mr-1"></i> المعلمون
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" style="border: none; border-bottom: 2px solid transparent; color: #7b86a6; font-weight: 600;" id="parents-tab" data-toggle="tab" href="#parents" role="tab" aria-controls="parents" aria-selected="false">
                                                    <i class="fas fa-user-tie mr-1"></i> أولياء الأمور
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="tab-content" id="myTabContent">
                                    <!-- Students Tab -->
                                    <div class="tab-pane fade active show" id="students" role="tabpanel" aria-labelledby="students-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover" style="color: #20263a;">
                                                <thead>
                                                    <tr style="background: #f4f7ff;">
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">#</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">اسم الطالب</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">البريد الإلكتروني</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">المرحلة</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">الصف</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">القسم</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">السنة الدراسية</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(\App\Models\Student::latest()->take(8)->get() as $student)
                                                    <tr style="border-bottom: 1px solid #eef1f8;">
                                                        <td style="padding: 12px 16px; color: #7b86a6; font-weight: 600;">{{ $loop->iteration }}</td>
                                                        <td style="padding: 12px 16px; font-weight: 600; color: #20263a;">
                                                            {{ is_array($student->name) ? ($student->name[ar] ?? $student->name[en] ?? ) : $student->name }}
                                                        </td>
                                                        <td style="padding: 12px 16px; color: #7b86a6;">{{ $student->email }}</td>
                                                        <td style="padding: 12px 16px;">
                                                            @if($student->grade)
                                                                <span style="background: rgba(95,124,247,0.1); color: #5f7cf7; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                                    {{ is_array($student->grade->Name) ? ($student->grade->Name[ar] ?? ) : $student->grade->Name }}
                                                                </span>
                                                            @else
                                                                <span style="color: #7b86a6;">-</span>
                                                            @endif
                                                        </td>
                                                        <td style="padding: 12px 16px;">
                                                            @if($student->classroom)
                                                                <span style="background: rgba(255,112,77,0.1); color: #ff704d; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                                    {{ is_array($student->classroom->Name_Class) ? ($student->classroom->Name_Class[ar] ?? ) : $student->classroom->Name_Class }}
                                                                </span>
                                                            @else
                                                                <span style="color: #7b86a6;">-</span>
                                                            @endif
                                                        </td>
                                                        <td style="padding: 12px 16px;">
                                                            @if($student->section)
                                                                <span style="background: rgba(46,204,113,0.1); color: #2ecc71; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                                    {{ is_array($student->section->Name_Section) ? ($student->section->Name_Section[ar] ?? ) : $student->section->Name_Section }}
                                                                </span>
                                                            @else
                                                                <span style="color: #7b86a6;">-</span>
                                                            @endif
                                                        </td>
                                                        <td style="padding: 12px 16px; color: #7b86a6; font-weight: 500;">{{ $student->academic_year }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center" style="padding: 40px; color: #7b86a6;">
                                                            <i class="fas fa-inbox" style="font-size: 32px; color: #dfe5f5; margin-bottom: 12px; display: block;"></i>
                                                            لا يوجد طلاب مسجلين
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Teachers Tab -->
                                    <div class="tab-pane fade" id="teachers" role="tabpanel" aria-labelledby="teachers-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover" style="color: #20263a;">
                                                <thead>
                                                    <tr style="background: #f4f7ff;">
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">#</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">اسم المعلم</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">التخصص</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">تاريخ الإنضمام</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(\App\Models\Teacher::latest()->take(8)->get() as $teacher)
                                                    <tr style="border-bottom: 1px solid #eef1f8;">
                                                        <td style="padding: 12px 16px; color: #7b86a6; font-weight: 600;">{{ $loop->iteration }}</td>
                                                        <td style="padding: 12px 16px; font-weight: 600; color: #20263a;">{{ $teacher->Name }}</td>
                                                        <td style="padding: 12px 16px;">
                                                            <span style="background: rgba(255,112,77,0.1); color: #ff704d; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                                {{ $teacher->specialization->Name ?? - }}
                                                            </span>
                                                        </td>
                                                        <td style="padding: 12px 16px; color: #7b86a6;">{{ $teacher->Joining_Date ?? - }}</td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center" style="padding: 40px; color: #7b86a6;">
                                                            <i class="fas fa-inbox" style="font-size: 32px; color: #dfe5f5; margin-bottom: 12px; display: block;"></i>
                                                            لا يوجد معلمين مسجلين
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Parents Tab -->
                                    <div class="tab-pane fade" id="parents" role="tabpanel" aria-labelledby="parents-tab">
                                        <div class="table-responsive">
                                            <table class="table table-hover" style="color: #20263a;">
                                                <thead>
                                                    <tr style="background: #f4f7ff;">
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">#</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">اسم ولي الأمر</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">رقم الهوية</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">رقم الهاتف</th>
                                                        <th style="color: #20263a; font-weight: 700; border-bottom: 2px solid #dfe5f5; padding: 14px 16px;">الوظيفة</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse(\App\Models\My_Parent::latest()->take(8)->get() as $parent)
                                                    <tr style="border-bottom: 1px solid #eef1f8;">
                                                        <td style="padding: 12px 16px; color: #7b86a6; font-weight: 600;">{{ $loop->iteration }}</td>
                                                        <td style="padding: 12px 16px; font-weight: 600; color: #20263a;">{{ $parent->Name_Father }}</td>
                                                        <td style="padding: 12px 16px; color: #7b86a6;">{{ $parent->National_ID_Father }}</td>
                                                        <td style="padding: 12px 16px; color: #7b86a6;">{{ $parent->Phone_Father }}</td>
                                                        <td style="padding: 12px 16px;">
                                                            <span style="background: rgba(255,200,76,0.12); color: #c49000; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600;">
                                                                {{ $parent->Job_Father ?? - }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    @empty
                                                    <tr>
                                                        <td colspan="5" class="text-center" style="padding: 40px; color: #7b86a6;">
                                                            <i class="fas fa-inbox" style="font-size: 32px; color: #dfe5f5; margin-bottom: 12px; display: block;"></i>
                                                            لا يوجد أولياء أمور مسجلين
                                                        </td>
                                                    </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            @include(layouts.footer)
        </div>
    </div>

    @include(layouts.footer-scripts)
</body>
</html>
