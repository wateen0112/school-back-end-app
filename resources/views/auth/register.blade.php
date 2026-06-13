@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card bg-surface-container-lowest border-0 shadow-lg rounded-xl">
                <div class="card-header bg-primary text-on-primary rounded-t-xl border-0">
                    <h4 class="mb-0 text-title-lg">{{ __('Register') }}</h4>
                </div>

                <div class="card-body p-4">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <div class="form-group row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-label-lg text-on-surface-variant text-md-right">{{ __('Name') }}</label>

                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror bg-surface-container border-outline-variant text-on-surface rounded-lg" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                @error('name')
                                    <span class="invalid-feedback text-error" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-label-lg text-on-surface-variant text-md-right">{{ __('E-Mail Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror bg-surface-container border-outline-variant text-on-surface rounded-lg" name="email" value="{{ old('email') }}" required autocomplete="email">

                                @error('email')
                                    <span class="invalid-feedback text-error" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-3">
                            <label for="password" class="col-md-4 col-form-label text-label-lg text-on-surface-variant text-md-right">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror bg-surface-container border-outline-variant text-on-surface rounded-lg" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback text-error" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <label for="password-confirm" class="col-md-4 col-form-label text-label-lg text-on-surface-variant text-md-right">{{ __('Confirm Password') }}</label>

                            <div class="col-md-6">
                                <input id="password-confirm" type="password" class="form-control bg-surface-container border-outline-variant text-on-surface rounded-lg" name="password_confirmation" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-6 py-2 transition-all transform hover:scale-105">
                                    {{ __('Register') }}
                                </button>
                            </div>
                        </div>
                    </form>
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

.card-header {
    border-radius: 16px 16px 0 0 !important;
}

.form-control {
    border-radius: 8px !important;
}

.btn {
    border-radius: 8px !important;
}

.form-group {
    border-radius: 6px;
}

.col-md-6, .col-md-4 {
    border-radius: 6px;
}

/* Enhanced styling */
.container {
    border-radius: 12px;
}

.row {
    border-radius: 12px;
}

/* Smooth transitions */
div, .form-control, .btn {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

.form-control:focus {
    transform: scale(1.02);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    div {
        border-radius: 6px;
    }
    
    .card {
        border-radius: 12px !important;
    }
    
    .card-header {
        border-radius: 12px 12px 0 0 !important;
    }
    
    .form-control {
        border-radius: 6px !important;
    }
}
</style>
@endpush
