{{-- Student Dashboard Sidebar --}}
<x-sidebar.shell :dashboard-url="route('dashboard.Students')">
    @include('layouts.main-sidebar.menus.student')
</x-sidebar.shell>
