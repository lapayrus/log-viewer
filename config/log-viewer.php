<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route
    |--------------------------------------------------------------------------
    |
    | The route where the log viewer will be available.
    |
    */
    'route' => 'log-viewer',

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Route Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that should be assigned to the log viewer route.
    |
    */
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Domain
    |--------------------------------------------------------------------------
    |
    | You may change the domain where log viewer should be available.
    | If this is null, the routes will be available on the same domain
    | that the application is running.
    |
    */
    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Path
    |--------------------------------------------------------------------------
    |
    | The path where the log files are stored.
    | This is the path relative to the storage directory.
    |
    */
    'path' => 'logs',

    /*
    |--------------------------------------------------------------------------
    | Log Viewer Pattern
    |--------------------------------------------------------------------------
    |
    | The pattern that will be used to find log files.
    |
    */
    'pattern' => '*.log',
];