<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>
            @if (View::hasSection('title'))
                @yield('title')
            @else
                Catapult - Bonfire Technologies and Solutions Corp.
            @endif
        </title>
        <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}" defer></script>
        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    </head>
    <body>
        <div id="app">
            <core>
                <top-navigation></top-navigation>
                <div class="page">
                    <side-navigation
                        page="@yield('page-link')">
                    </side-navigation>
                    <div class="main-content-container">
                        <div class="main-content-title">
                            @yield('title')
                        </div>
                        @yield('content')
                        <footer-panel></footer-panel>
                    </div>
                </div>
            </core>
        </div>
    </body>
</html>