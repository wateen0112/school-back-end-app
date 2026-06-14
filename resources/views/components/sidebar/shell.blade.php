@props([
    'dashboardUrl' => url('/dashboard'),
])

<div class="scrollbar side-menu-bg" style="overflow: scroll">
    <ul class="nav navbar-nav side-menu" id="sidebarnav">
        <!-- Dashboard Link -->
        <li>
            <a href="{{ $dashboardUrl }}">
                <div class="pull-left">
                    <i class="ti-home" style="color: #5f7cf7;"></i>
                    <span class="right-nav-text">{{ trans('main_trans.Dashboard') }}</span>
                </div>
                <div class="clearfix"></div>
            </a>
        </li>

        <!-- Section Divider -->
        <li class="mt-10 mb-10 text-muted pl-4 font-medium menu-title">{{ trans('main_trans.Programname') }}</li>

        {{ $slot }}
    </ul>
</div>
