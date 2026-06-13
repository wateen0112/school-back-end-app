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
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    @include('layouts.head')
    @livewireStyles
</head>

<body style="font-family: 'Cairo', sans-serif" class="bg-background text-on-background">

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
            <div class="page-title bg-surface-container border-b border-outline-variant" >
                <div class="row">
                    <div class="col-sm-6" >
                        <h4 class="mb-0 text-title-lg text-primary" style="font-family: 'Cairo', sans-serif">لوحة تحكم الادمن</h4>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb pt-0 pr-0 float-left float-sm-right bg-transparent">
                            <li class="breadcrumb-item"><a href="#" class="text-primary">الرئيسية</a></li>
                            <li class="breadcrumb-item active text-on-surface-variant">لوحة التحكم</li>
                        </ol>
                    </div>
                </div>
            </div>
            <!-- widgets -->
            <div class="row p-4" >
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg fade-in rounded-xl">
                        <div class="card-body">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div class="w-14 h-14 rounded-full bg-primary-container/20 flex items-center justify-center">
                                        <i class="fas fa-user-graduate text-2xl text-primary"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p class="card-text text-label-lg text-on-surface-variant">عدد الطلاب</p>
                                    <h4 class="text-display-lg text-primary mb-0">{{\App\Models\Student::count()}}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2 border-t border-outline-variant">
                                <i class="fas fa-arrow-left mr-1 text-primary" aria-hidden="true"></i><a href="{{route('Students.index')}}" target="_blank" class="text-primary hover:text-primary-container transition-colors">عرض البيانات</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg fade-in rounded-xl">
                        <div class="card-body">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div class="w-14 h-14 rounded-full bg-secondary-container/20 flex items-center justify-center">
                                        <i class="fas fa-chalkboard-teacher text-2xl text-secondary"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p class="card-text text-label-lg text-on-surface-variant">عدد المعلمين</p>
                                    <h4 class="text-display-lg text-secondary mb-0">{{\App\Models\Teacher::count()}}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2 border-t border-outline-variant">
                                <i class="fas fa-arrow-left mr-1 text-secondary" aria-hidden="true"></i><a href="{{route('Teachers.index')}}" target="_blank" class="text-secondary hover:text-secondary-container transition-colors">عرض البيانات</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg fade-in rounded-xl">
                        <div class="card-body">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div class="w-14 h-14 rounded-full bg-tertiary-container/20 flex items-center justify-center">
                                        <i class="fas fa-user-tie text-2xl text-tertiary"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p class="card-text text-label-lg text-on-surface-variant">عدد اولياء الامور</p>
                                    <h4 class="text-display-lg text-tertiary mb-0">{{\App\Models\My_Parent::count()}}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2 border-t border-outline-variant">
                                <i class="fas fa-arrow-left mr-1 text-tertiary" aria-hidden="true"></i><a href="{{route('add_parent')}}" target="_blank" class="text-tertiary hover:text-tertiary-container transition-colors">عرض البيانات</a>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 mb-30">
                    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg fade-in rounded-xl">
                        <div class="card-body">
                            <div class="clearfix">
                                <div class="float-left">
                                    <div class="w-14 h-14 rounded-full bg-surface-container flex items-center justify-center">
                                        <i class="fas fa-chalkboard text-2xl text-on-surface"></i>
                                    </div>
                                </div>
                                <div class="float-right text-right">
                                    <p class="card-text text-label-lg text-on-surface-variant">عدد الفصول الدراسية</p>
                                    <h4 class="text-display-lg text-on-surface mb-0">{{\App\Models\Section::count()}}</h4>
                                </div>
                            </div>
                            <p class="text-muted pt-3 mb-0 mt-2 border-t border-outline-variant">
                                <i class="fas fa-arrow-left mr-1 text-on-surface" aria-hidden="true"></i><a href="{{route('Sections.index')}}" target="_blank" class="text-on-surface hover:text-surface-container-high transition-colors">عرض البيانات</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Orders Status widgets-->


            <div class="row px-4">

                <div  style="height: 400px;" class="col-xl-12 mb-30">
                    <div class="card card-statistics h-100 bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                        <div class="card-body">
                            <div class="tab nav-border" style="position: relative;">
                                <div class="d-block d-md-flex justify-content-between border-b border-outline-variant pb-3">
                                    <div class="d-block w-100">
                                        <h5 style="font-family: 'Cairo', sans-serif" class="card-title text-title-lg text-on-surface">اخر العمليات علي النظام</h5>
                                    </div>
                                    <div class="d-block d-md-flex nav-tabs-custom">
                                        <ul class="nav nav-tabs border-0" id="myTab" role="tablist">

                                            <li class="nav-item">
                                                <a class="nav-link active show bg-transparent border-0 border-b-2 border-primary text-primary" id="students-tab" data-toggle="tab"
                                                   href="#students" role="tab" aria-controls="students"
                                                   aria-selected="true"> الطلاب</a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link bg-transparent border-0 border-b-2 border-transparent text-on-surface-variant hover:text-primary" id="teachers-tab" data-toggle="tab" href="#teachers"
                                                   role="tab" aria-controls="teachers" aria-selected="false">المعلمين
                                                </a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link bg-transparent border-0 border-b-2 border-transparent text-on-surface-variant hover:text-primary" id="parents-tab" data-toggle="tab" href="#parents"
                                                   role="tab" aria-controls="parents" aria-selected="false">اولياء الامور
                                                </a>
                                            </li>

                                            <li class="nav-item">
                                                <a class="nav-link bg-transparent border-0 border-b-2 border-transparent text-on-surface-variant hover:text-primary" id="fee_invoices-tab" data-toggle="tab" href="#fee_invoices"
                                                   role="tab" aria-controls="fee_invoices" aria-selected="false">الفواتير
                                                </a>
                                            </li>

                                        </ul>
                                    </div>
                                </div>
                                <div class="tab-content" id="myTabContent">

                                    {{--students Table--}}
                                    <div class="tab-pane fade active show" id="students" role="tabpanel" aria-labelledby="students-tab">
                                        <div class="table-responsive mt-15">
                                            <table style="text-align: center" class="table center-aligned-table table-hover mb-0">
                                                <thead>
                                                <tr  class="bg-surface-container">
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">#</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم الطالب</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">البريد الالكتروني</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">النوع</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">المرحلة الدراسية</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">الصف الدراسي</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">القسم</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ الاضافة</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-outline-variant">
                                                @forelse(\App\Models\Student::latest()->take(5)->get() as $student)
                                                    <tr class="hover:bg-surface-container-high transition-colors">
                                                        <td class="px-4 py-4">{{$loop->iteration}}</td>
                                                        <td class="px-4 py-4">
                                                            <div class="flex items-center justify-center gap-2">
                                                                <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs">
                                                                    {{mb_substr($student->name, 0, 2)}}
                                                                </div>
                                                                <span class="text-body-md">{{$student->name}}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$student->email}}</td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                                                                {{$student->gender->Name}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                                                {{$student->grade->Name}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                                {{$student->classroom->Name_Class}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-surface-container px-2 py-1 rounded text-xs text-on-surface">
                                                                {{$student->section->Name_Section}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-success">{{$student->created_at}}</td>
                                                        @empty
                                                            <td class="px-4 py-8 text-center text-on-surface-variant" colspan="8">
                                                                <i class="fas fa-inbox text-3xl mb-2 block text-outline"></i>
                                                                لاتوجد بيانات
                                                            </td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{--teachers Table--}}
                                    <div class="tab-pane fade" id="teachers" role="tabpanel" aria-labelledby="teachers-tab">
                                        <div class="table-responsive mt-15">
                                            <table style="text-align: center" class="table center-aligned-table table-hover mb-0">
                                                <thead>
                                                <tr  class="bg-surface-container">
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">#</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم المعلم</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">النوع</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ التعين</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">التخصص</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ الاضافة</th>
                                                </tr>
                                                </thead>

                                                @forelse(\App\Models\Teacher::latest()->take(5)->get() as $teacher)
                                                    <tbody class="divide-y divide-outline-variant">
                                                    <tr class="hover:bg-surface-container-high transition-colors">
                                                        <td class="px-4 py-4">{{$loop->iteration}}</td>
                                                        <td class="px-4 py-4">
                                                            <div class="flex items-center justify-center gap-2">
                                                                <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                                                                    {{mb_substr($teacher->Name, 0, 2)}}
                                                                </div>
                                                                <span class="text-body-md">{{$teacher->Name}}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                                                {{$teacher->genders->Name}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$teacher->Joining_Date}}</td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                                {{$teacher->specializations->Name}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-success">{{$teacher->created_at}}</td>
                                                        @empty
                                                            <td class="px-4 py-8 text-center text-on-surface-variant" colspan="8">
                                                                <i class="fas fa-inbox text-3xl mb-2 block text-outline"></i>
                                                                لاتوجد بيانات
                                                            </td>
                                                    </tr>
                                                    </tbody>
                                                @endforelse
                                            </table>
                                        </div>
                                    </div>

                                    {{--parents Table--}}
                                    <div class="tab-pane fade" id="parents" role="tabpanel" aria-labelledby="parents-tab">
                                        <div class="table-responsive mt-15">
                                            <table style="text-align: center" class="table center-aligned-table table-hover mb-0">
                                                <thead>
                                                <tr  class="bg-surface-container">
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">#</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم ولي الامر</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">البريد الالكتروني</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">رقم الهوية</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">رقم الهاتف</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ الاضافة</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-outline-variant">
                                                @forelse(\App\Models\My_Parent::latest()->take(5)->get() as $parent)
                                                    <tr class="hover:bg-surface-container-high transition-colors">
                                                        <td class="px-4 py-4">{{$loop->iteration}}</td>
                                                        <td class="px-4 py-4">
                                                            <div class="flex items-center justify-center gap-2">
                                                                <div class="w-8 h-8 rounded-full bg-tertiary-container flex items-center justify-center text-tertiary text-xs">
                                                                    {{mb_substr($parent->Name_Father, 0, 2)}}
                                                                </div>
                                                                <span class="text-body-md">{{$parent->Name_Father}}</span>
                                                            </div>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$parent->email}}</td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$parent->National_ID_Father}}</td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$parent->Phone_Father}}</td>
                                                        <td class="px-4 py-4 text-body-md text-success">{{$parent->created_at}}</td>
                                                        @empty
                                                            <td class="px-4 py-8 text-center text-on-surface-variant" colspan="8">
                                                                <i class="fas fa-inbox text-3xl mb-2 block text-outline"></i>
                                                                لاتوجد بيانات
                                                            </td>
                                                    </tr>
                                                @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    {{--sections Table--}}
                                    <div class="tab-pane fade" id="fee_invoices" role="tabpanel" aria-labelledby="fee_invoices-tab">
                                        <div class="table-responsive mt-15">
                                            <table style="text-align: center" class="table center-aligned-table table-hover mb-0">
                                                <thead>
                                                <tr  class="bg-surface-container">
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">#</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ الفاتورة</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">اسم الطالب</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">المرحلة الدراسية</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">الصف الدراسي</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">القسم</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">نوع الرسوم</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">المبلغ</th>
                                                    <th class="px-4 py-3 text-label-lg text-on-surface-variant">تاريخ الاضافة</th>
                                                </tr>
                                                </thead>
                                                <tbody class="divide-y divide-outline-variant">
                                                @forelse(\App\Models\Fee_invoice::latest()->take(10)->get() as $section)
                                                    <tr class="hover:bg-surface-container-high transition-colors">
                                                        <td class="px-4 py-4">{{$loop->iteration}}</td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$section->invoice_date}}</td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$section->student->name ?? 'N/A'}}</td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                                                                {{$section->grade->Name ?? 'N/A'}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-secondary/15 text-secondary px-2 py-1 rounded text-xs">
                                                                {{$section->My_classs->Name_Class}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4">
                                                            <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                                                                {{$section->section->Name_Section ?? 'N/A'}}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-on-surface-variant">{{$section->fee->title_ar ?? 'N/A'}}</td>
                                                        <td class="px-4 py-4">
                                                            <span class="text-body-md font-semibold text-success">
                                                                {{number_format($section->amount ?? 0, 2)}} ج.م
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-4 text-body-md text-success">{{$section->created_at}}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td class="px-4 py-8 text-center text-on-surface-variant" colspan="9">
                                                            <i class="fas fa-inbox text-3xl mb-2 block text-outline"></i>
                                                            لاتوجد بيانات
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

            <livewire:calendar />

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
    @livewireScripts
    @stack('scripts')

</body>

</html>

<style>
/* Custom styles for the new dashboard design */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border-radius: 16px !important;
}

.card:hover {
    transform: translateY(-2px);
}

.fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 12px 12px 0 0;
    padding: 12px 16px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.nav-tabs .nav-link.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: transparent;
}

.nav-tabs .nav-link:hover {
    color: var(--primary);
    background: var(--surface-container);
    border-radius: 12px 12px 0 0;
}

/* Enhanced table styling */
.table {
    border-radius: 12px !important;
    overflow: hidden;
}

.table-hover tbody tr:hover {
    background-color: var(--surface-container-high);
}

.table thead th {
    border-top: none;
    border-radius: 0;
}

.table thead th:first-child {
    border-top-left-radius: 12px;
}

.table thead th:last-child {
    border-top-right-radius: 12px;
}

/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.card-body {
    border-radius: 16px !important;
}

.card-header {
    border-radius: 16px 16px 0 0 !important;
    border-bottom: 1px solid var(--outline-variant);
}

.page-title {
    border-radius: 16px !important;
}

.breadcrumb {
    border-radius: 8px !important;
}

.table-responsive {
    border-radius: 12px !important;
}

.tab-content {
    border-radius: 0 0 16px 16px !important;
}

.tab-pane {
    border-radius: 0 0 16px 16px !important;
}

.clearfix {
    border-radius: 8px;
}

.float-left, .float-right {
    border-radius: 8px;
}

/* Button and link border radius */
.btn {
    border-radius: 8px !important;
}

a {
    border-radius: 6px;
}

/* Input and form elements */
input, select, textarea {
    border-radius: 8px !important;
}

/* Badge styling */
.badge, span[class*="bg-"] {
    border-radius: 6px !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-xl-3 {
        margin-bottom: 1rem;
    }
    
    div {
        border-radius: 6px;
    }
    
    .card {
        border-radius: 12px !important;
    }
    
    .card-body {
        border-radius: 12px !important;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }
}

/* Smooth transitions for border radius */
div, .card, .card-body, .card-header, .table, .table-responsive, .tab-content, .tab-pane {
    transition: border-radius 0.3s ease;
}
</style>
