<?php

namespace App\Providers;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register custom exception handler
        $this->app->singleton(ExceptionHandler::class, \App\Exceptions\Handler::class);

        // Register flashcard services
        $this->app->singleton(\App\Services\AnkiImportService::class);
        $this->app->singleton(\App\Services\MnemosyneImportService::class);
        $this->app->singleton(\App\Services\DuplicateDetectionService::class);
        $this->app->singleton(\App\Services\MediaStorageService::class);
        $this->app->singleton(\App\Services\FlashcardImportService::class);
        $this->app->singleton(\App\Services\FlashcardExportService::class);
        $this->app->singleton(\App\Services\FlashcardPrintService::class);
        $this->app->singleton(\App\Services\FlashcardCacheService::class);
        $this->app->singleton(\App\Services\FlashcardSearchService::class);
        $this->app->singleton(\App\Services\FlashcardPerformanceService::class);
        $this->app->singleton(\App\Services\FlashcardErrorService::class);

        // Register date/time formatting service
        $this->app->singleton(\App\Services\DateTimeFormatterService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
