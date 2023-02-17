<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="stylesheet" href="{{ elixir('css/app.css') }}">
        <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
        <link rel="icon" href="favicon.ico" type="image/x-icon">
        <title>Catapult - Login</title>
        <script>
            document.getElementsByTagName("html")[0].style.visibility = "hidden";
        </script>
    </head>
    <body class="login">
        <div id="app">
            <core>
                <login></login>
            </core>
        </div>
        <script src="{{ asset('js/app.js') }}"></script>
        <script>
            document.getElementsByTagName("html")[0].style.visibility = "visible";
        </script>
    </body>
</html>
