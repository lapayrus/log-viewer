<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapayrus Log Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    @include('log-viewer::includes.styles')
</head>
<body>
    <div class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="h4 mb-0">Lapayrus Log Viewer</h1>
            </div>
            @yield('header')
        </div>
    </div>

    <div class="container">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    @include('log-viewer::includes.scripts')
</body>
</html>