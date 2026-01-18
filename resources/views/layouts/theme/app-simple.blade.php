<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JSPOS v1.7 - Acceso</title>

    @include('layouts.theme.styles')
    @stack('my-styles')
    
    <style>
        body {
            background-color: #f1f2f3;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>

    <div class="container">
        @yield('content')
    </div>

    <!-- scripts -->
    @include('layouts.theme.scripts')
    @stack('my-scripts')

</body>
</html>
