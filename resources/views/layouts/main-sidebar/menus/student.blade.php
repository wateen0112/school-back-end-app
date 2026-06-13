{{-- Student dashboard sidebar items --}}

<li>
    <a href="{{ route('student_exams.index') }}"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.Student_Exams') }}</span></a>
</li>
<li>
    <a href="{{ route('profile-student.index') }}"><i class="fas fa-id-card-alt"></i><span class="right-nav-text">{{ trans('main_trans.Profile') }}</span></a>
</li>
