<?php

namespace App\Providers;

use App\Services\Ai\Contracts\AiClient;
use App\Services\Ai\GeminiClient;
use App\Services\Ai\KimiClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(AiClient::class, function () {
            $provider = strtolower(config('services.ai.provider', 'gemini'));

            return match ($provider) {
                'kimi' => new KimiClient(),
                default => new GeminiClient(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
