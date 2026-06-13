{{-- Teacher dashboard sidebar items --}}

<li>
    <a href="{{ route('sections') }}"><i class="fas fa-chalkboard"></i><span class="right-nav-text">{{ trans('main_trans.Teacher_Sections') }}</span></a>
</li>
<li>
    <a href="{{ route('student.index') }}"><i class="fas fa-user-graduate"></i><span class="right-nav-text">{{ trans('main_trans.Teacher_Students') }}</span></a>
</li>

<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#teacher-tests-menu">
        <div class="pull-left"><i class="fas fa-chalkboard"></i><span class="right-nav-text">{{ trans('main_trans.Teacher_Tests') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="teacher-tests-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('quizzes.index') }}">{{ trans('main_trans.Quizzes_list') }}</a></li>
        <li><a href="{{ route('questions.index') }}">{{ trans('main_trans.Questions_list_menu') }}</a></li>
    </ul>
</li>

<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#teacher-online-classes">
        <div class="pull-left"><i class="fas fa-video"></i><span class="right-nav-text">{{ trans('main_trans.Onlineclasses') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="teacher-online-classes" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('online_zoom_classes.index') }}">{{ trans('main_trans.Online_Zoom_Classes') }}</a></li>
    </ul>
</li>

<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#teacher-reports-menu">
        <div class="pull-left"><i class="fas fa-chalkboard"></i><span class="right-nav-text">{{ trans('main_trans.Teacher_Reports') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="teacher-reports-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('attendance.report') }}">{{ trans('main_trans.Attendance_Report_menu') }}</a></li>
        <li><a href="#">{{ trans('main_trans.Exams_Report') }}</a></li>
    </ul>
</li>

<li>
    <a href="{{ route('profile.show') }}"><i class="fas fa-id-card-alt"></i><span class="right-nav-text">{{ trans('main_trans.Profile') }}</span></a>
</li>
