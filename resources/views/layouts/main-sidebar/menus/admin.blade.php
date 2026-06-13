{{-- Admin dashboard sidebar items (Arabic via main_trans when locale is ar) --}}

{{-- Grades --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Grades-menu">
        <div class="pull-left"><i class="fas fa-school"></i><span class="right-nav-text">{{ trans('main_trans.Grades') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Grades-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Grades.index') }}">{{ trans('main_trans.Grades_list') }}</a></li>
    </ul>
</li>

{{-- Classes --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#classes-menu">
        <div class="pull-left"><i class="fa fa-building"></i><span class="right-nav-text">{{ trans('main_trans.classes') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="classes-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Classrooms.index') }}">{{ trans('main_trans.List_classes') }}</a></li>
    </ul>
</li>

{{-- Sections --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#sections-menu">
        <div class="pull-left"><i class="fas fa-chalkboard"></i><span class="right-nav-text">{{ trans('main_trans.sections') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="sections-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Sections.index') }}">{{ trans('main_trans.List_sections') }}</a></li>
    </ul>
</li>

{{-- Students --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#students-menu"><i class="fas fa-user-graduate"></i>{{ trans('main_trans.students') }}<div class="pull-right"><i class="ti-plus"></i></div><div class="clearfix"></div></a>
    <ul id="students-menu" class="collapse">
        <li>
            <a href="javascript:void(0);" data-toggle="collapse" data-target="#Student_information">{{ trans('main_trans.Student_information') }}<div class="pull-right"><i class="ti-plus"></i></div><div class="clearfix"></div></a>
            <ul id="Student_information" class="collapse">
                <li><a href="{{ route('Students.create') }}">{{ trans('main_trans.add_student') }}</a></li>
                <li><a href="{{ route('Students.index') }}">{{ trans('main_trans.list_students') }}</a></li>
            </ul>
        </li>
        <li>
            <a href="javascript:void(0);" data-toggle="collapse" data-target="#Students_upgrade">{{ trans('main_trans.Students_Promotions') }}<div class="pull-right"><i class="ti-plus"></i></div><div class="clearfix"></div></a>
            <ul id="Students_upgrade" class="collapse">
                <li><a href="{{ route('Promotion.index') }}">{{ trans('main_trans.add_Promotion') }}</a></li>
                <li><a href="{{ route('Promotion.create') }}">{{ trans('main_trans.list_Promotions') }}</a></li>
            </ul>
        </li>
        <li>
            <a href="javascript:void(0);" data-toggle="collapse" data-target="#Graduate_students">{{ trans('main_trans.Graduate_students') }}<div class="pull-right"><i class="ti-plus"></i></div><div class="clearfix"></div></a>
            <ul id="Graduate_students" class="collapse">
                <li><a href="{{ route('Graduated.create') }}">{{ trans('main_trans.add_Graduate') }}</a></li>
                <li><a href="{{ route('Graduated.index') }}">{{ trans('main_trans.list_Graduate') }}</a></li>
            </ul>
        </li>
    </ul>
</li>

{{-- Teachers --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Teachers-menu">
        <div class="pull-left"><i class="fas fa-chalkboard-teacher"></i><span class="right-nav-text">{{ trans('main_trans.Teachers') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Teachers-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Teachers.index') }}">{{ trans('main_trans.List_Teachers') }}</a></li>
    </ul>
</li>

{{-- Parents --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Parents-menu">
        <div class="pull-left"><i class="fas fa-user-tie"></i><span class="right-nav-text">{{ trans('main_trans.Parents') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Parents-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ url('add_parent') }}">{{ trans('main_trans.List_Parents') }}</a></li>
    </ul>
</li>

{{-- Accounts --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Accounts-menu">
        <div class="pull-left"><i class="fas fa-money-bill-wave-alt"></i><span class="right-nav-text">{{ trans('main_trans.Accounts') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Accounts-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Fees.index') }}">{{ trans('main_trans.Tuition_Fees') }}</a></li>
        <li><a href="{{ route('Fees_Invoices.index') }}">{{ trans('main_trans.Invoices') }}</a></li>
        <li><a href="{{ route('receipt_students.index') }}">{{ trans('main_trans.Receipt_Vouchers') }}</a></li>
        <li><a href="{{ route('ProcessingFee.index') }}">{{ trans('main_trans.Fee_Exclusion') }}</a></li>
        <li><a href="{{ route('Payment_students.index') }}">{{ trans('main_trans.Payment_Vouchers') }}</a></li>
    </ul>
</li>

{{-- Attendance --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Attendance-icon">
        <div class="pull-left"><i class="fas fa-calendar-alt"></i><span class="right-nav-text">{{ trans('main_trans.Attendance') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Attendance-icon" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Attendance.index') }}">{{ trans('main_trans.Students_Attendance_List') }}</a></li>
    </ul>
</li>

{{-- Subjects --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Subjects-menu">
        <div class="pull-left"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.Subjects') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Subjects-menu" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('subjects.index') }}">{{ trans('main_trans.Subjects_list') }}</a></li>
    </ul>
</li>

{{-- Quizzes / exams --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Exams-icon">
        <div class="pull-left"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.Exams') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Exams-icon" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('Quizzes.index') }}">{{ trans('main_trans.Quizzes_list') }}</a></li>
        <li><a href="{{ route('questions.index') }}">{{ trans('main_trans.Questions_list_menu') }}</a></li>
    </ul>
</li>

{{-- Library --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#library-icon">
        <div class="pull-left"><i class="fas fa-book"></i><span class="right-nav-text">{{ trans('main_trans.library') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="library-icon" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('library.index') }}">{{ trans('main_trans.Books_list') }}</a></li>
    </ul>
</li>

{{-- Online classes --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Onlineclasses-icon">
        <div class="pull-left"><i class="fas fa-video"></i><span class="right-nav-text">{{ trans('main_trans.Onlineclasses') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Onlineclasses-icon" class="collapse" data-parent="#sidebarnav">
        <li><a href="{{ route('online_classes.index') }}">{{ trans('main_trans.Online_Zoom_Classes') }}</a></li>
    </ul>
</li>

{{-- Settings --}}
<li>
    <a href="{{ route('settings.index') }}"><i class="fas fa-cogs"></i><span class="right-nav-text">{{ trans('main_trans.Settings') }}</span></a>
</li>

{{-- Users (legacy icon demo links) --}}
<li>
    <a href="javascript:void(0);" data-toggle="collapse" data-target="#Users-icon">
        <div class="pull-left"><i class="fas fa-users"></i><span class="right-nav-text">{{ trans('main_trans.Users') }}</span></div>
        <div class="pull-right"><i class="ti-plus"></i></div>
        <div class="clearfix"></div>
    </a>
    <ul id="Users-icon" class="collapse" data-parent="#sidebarnav">
        <li><a href="fontawesome-icon.html">{{ trans('main_trans.Icon_FontAwesome') }}</a></li>
        <li><a href="themify-icons.html">{{ trans('main_trans.Icon_Themify') }}</a></li>
        <li><a href="weather-icon.html">{{ trans('main_trans.Icon_Weather') }}</a></li>
    </ul>
</li>
