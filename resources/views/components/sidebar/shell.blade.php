@props([
    'dashboardUrl' => url('/dashboard'),
])

<div class="scrollbar side-menu-bg" style="overflow: scroll">
    <ul class="nav navbar-nav side-menu" id="sidebarnav">
        <li>
            <a href="{{ $dashboardUrl }}">
                <div class="pull-left">
                    <i class="ti-home"></i>
                    <span class="right-nav-text">{{ trans('main_trans.Dashboard') }}</span>
                </div>
                <div class="clearfix"></div>
            </a>
        </li>
        <li class="mt-10 mb-10 text-muted pl-4 font-medium menu-title">{{ trans('main_trans.Programname') }}</li>

        {{ $slot }}
    </ul>
</div>
