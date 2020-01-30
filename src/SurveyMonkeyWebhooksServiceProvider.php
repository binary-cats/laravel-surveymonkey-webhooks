<?php

namespace BinaryCats\SurveyMonkeyWebhooks;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SurveyMonkeyWebhooksServiceProvider extends ServiceProvider
{
    /**
     * Boot application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/surveymonkey-webhooks.php' => config_path('surveymonkey-webhooks.php'),
            ], 'config');
        }

        Route::macro('surveyMonkeyWebhooks', function ($url) {
            return Route::post($url, '\BinaryCats\SurveyMonkeyWebhooks\SurveyMonkeyWebhooksController');
        });
    }

    /**
     * Register application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/surveymonkey-webhooks.php', 'surveymonkey-webhooks');
    }
}
