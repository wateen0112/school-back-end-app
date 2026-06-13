@extends('layouts.app')

@section('content')
<div class="container p-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-header bg-primary text-on-primary rounded-t-xl border-0">{{ __('Dashboard') }}</div>

                <div class="card-body p-4">
                    @if (session('status'))
                        <div class="alert bg-success-container text-on-success border-0 rounded-lg mb-4" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <p class="text-body-lg text-on-surface">{{ __('You are logged in!') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.card {
    border-radius: 16px !important;
}

.card-body {
    border-radius: 16px !important;
}

.card-header {
    border-radius: 16px 16px 0 0 !important;
}

.alert {
    border-radius: 8px !important;
}

/* Enhanced styling */
.container {
    border-radius: 12px;
}

.row {
    border-radius: 12px;
}

.col-md-8 {
    border-radius: 10px;
}

/* Smooth transitions */
div, .card, .card-body, .card-header, .alert {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.card:hover {
    transform: translateY(-2px);
}

/* Responsive adjustments */
@media (max-width: 768px) {
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
    
    .alert {
        border-radius: 6px !important;
    }
}
</style>
@endpush
