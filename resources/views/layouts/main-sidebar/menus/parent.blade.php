{{-- Parent dashboard sidebar items --}}

<li>
    <a href="{{ route('sons.index') }}"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.My_Children') }}</span></a>
</li>
<li>
    <a href="{{ route('sons.attendances') }}"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.Attendance_Report_menu') }}</span></a>
</li>
<li>
    <a href="{{ route('sons.fees') }}"><i class="fas fa-book-open"></i><span class="right-nav-text">{{ trans('main_trans.Financial_Report') }}</span></a>
</li>
<li>
    <a href="{{ route('profile.show.parent') }}"><i class="fas fa-id-card-alt"></i><span class="right-nav-text">{{ trans('main_trans.Profile') }}</span></a>
</li>
