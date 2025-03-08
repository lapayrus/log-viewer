<?php

use Illuminate\Support\Facades\Route;
use Lapayrus\LogViewer\Http\Controllers\LogViewerController;

Route::group([
    'prefix' => config('log-viewer.route', 'log-viewer'),
    'middleware' => config('log-viewer.middleware', ['web']),
    'domain' => config('log-viewer.domain'),
], function () {
    // Main log viewer routes
    Route::get('/', [LogViewerController::class, 'index'])
        ->name('log-viewer.index');
    
    Route::get('/file/{file}', [LogViewerController::class, 'show'])
        ->name('log-viewer.show');
    
    Route::get('/search', [LogViewerController::class, 'search'])
        ->name('log-viewer.search');
});