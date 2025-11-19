<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta content="Cool AgriStock, Food Storage" name="description" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta content="Cool AgriStock" name="author" />

        <meta name="app-url" content="{{ env('APP_URL') }}">

        <title>{{ config('app.name', 'Laravel') }} | Admin</title>

        <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

        @stack('links')

        <script src="{{ asset('js/pages/layout.js') }}"></script>

        <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('libs/simplebar/simplebar.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
    </head>
    <body>
        <div id="layout-wrapper">        
            <x-header :notifications="$notifications"></x-header>

            <x-sidebar></x-sidebar>

            <div class="main-content">
                <div class="page-content">
                    <div class="container-fluid">
                        {{ $slot }}
                    </div>
                </div>

                <x-footer></x-footer>
            </div>
        </div>

        <x-custom-settings></x-custom-settings>

        <!-- JAVASCRIPT -->
        <script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ asset('libs/node-waves/waves.min.js') }}"></script>
        <!-- stack -->
        @stack('scripts')

        <!-- App js -->
        <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>
