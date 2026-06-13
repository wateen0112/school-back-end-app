{{-- Admin Dashboard Sidebar --}}
<x-sidebar.shell :dashboard-url="url('/dashboard')">
    @include('layouts.main-sidebar.menus.admin')
</x-sidebar.shell>
