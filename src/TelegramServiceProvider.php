<?php

namespace Flamix\TelegramLog;

use Illuminate\Support\ServiceProvider;

/** Wires the `telegram` log channel into a Laravel app. */
class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/telegram-log.php', 'telegram-log');
    }

    /** Adds the default `telegram` channel and the publishable config and views. */
    public function boot(): void
    {
        // An app's own logging.channels.telegram wins entirely: its level, and nothing merged in.
        if (empty(config('logging.channels.telegram', []))) {
            config([
                'logging.channels.telegram' => [
                    'driver' => 'custom',
                    'via' => TelegramLogger::class,
                    'level' => 'debug',
                ]
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/views', 'laravel-telegram-log');
        $this->publishes([__DIR__.'/views' => base_path('resources/views/vendor/laravel-telegram-log')], 'views');
        $this->publishes([__DIR__ . '/../config/telegram-log.php' => config_path('telegram-log.php')], 'config');
    }
}
