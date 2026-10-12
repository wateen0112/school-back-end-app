<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        using: function () {
            $namespace = 'App\\Http\\Controllers';

            Route::middleware('web')
                ->namespace($namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->namespace($namespace)
                ->group(base_path('routes/student.php'));

            Route::middleware('web')
                ->namespace($namespace)
                ->group(base_path('routes/parent.php'));

            Route::middleware('web')
                ->namespace($namespace)
                ->group(base_path('routes/teacher.php'));

            Route::middleware('web')
                ->namespace($namespace)
                ->group(base_path('routes/ajax.php'));

            Route::prefix('api')
                ->middleware('api')
                ->namespace($namespace)
                ->group(base_path('routes/api.php'));
        },
        commands: base_path('routes/console.php'),
    )
    ->withSchedule(function ($schedule) {
        $schedule->command('installments:send-reminders')->dailyAt('08:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.any' => \App\Http\Middleware\AuthenticateAnyApiUser::class,
            'is.super.admin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'permission' => \App\Http\Middleware\EnsurePermission::class,
            'localize' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes::class,
            'localizationRedirect' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
            'localeCookieRedirect' => \Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect::class,
            'localeViewPath' => \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('selection'));

        // Stateful Sanctum SPA auth is intentionally disabled: the eduadmin-dashboard
        // uses token-based auth (personal access tokens stored in localStorage).
        // Enabling statefulApi() would require CSRF cookies on /api/* routes.
        // $middleware->statefulApi();

        // Resolve the app locale (ar/en) for API responses from ?locale=/?lang=
        // or the lang/X-Lang/X-Locale/Accept-Language headers sent by the clients.
        $middleware->prependToGroup('api', \App\Http\Middleware\SetApiLocale::class);

        // Localise the `message`/`errors` text of every JSON response using the
        // locale resolved above, so `lang: ar` yields Arabic error messages.
        $middleware->appendToGroup('api', \App\Http\Middleware\LocalizeApiResponse::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Every API request and /api/* web request gets the same JSON envelope:
        // { success: bool, message: string, data?: mixed, errors?: object }
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => \App\Support\ApiMessage::translate('Unauthenticated.'),
                ], 401);
            }

            return redirect()->guest(route('selection'));
        });

        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    // Laravel already returns the locale specific rule text; only
                    // the envelope headline needs translating.
                    'message' => \App\Support\ApiMessage::translate('Validation errors'),
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (!($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $status = 500;
            // Translate the canonical headlines; keep raw debug/exception text as-is.
            $message = config('app.debug') ? $e->getMessage() : \App\Support\ApiMessage::translate('Server error.');

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                $status = 404;
                $message = \App\Support\ApiMessage::translate('Resource not found.');
            } elseif ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                $status = $e->getStatusCode();
                $message = \App\Support\ApiMessage::translate($e->getMessage() ?: 'Request failed.');
            } elseif ($e instanceof \Illuminate\Database\QueryException) {
                $status = 500;
                $message = \App\Support\ApiMessage::translate('Database error.');
            }

            \App\Services\AuditLogger::log('api.request_failed', 'api', null, null, [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $status,
            ]);

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        });
    })
    ->create();
