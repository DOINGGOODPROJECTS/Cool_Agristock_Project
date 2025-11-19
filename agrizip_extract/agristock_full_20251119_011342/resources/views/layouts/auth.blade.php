<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta content="Cool AgriStock, Food Storage" name="description" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta content="Cool AgriStock" name="author" />

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}">

        <script src="{{ asset('js/pages/layout.js') }}"></script>

        <link href="{{ asset('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <link href="{{ asset('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ asset('libs/simplebar/simplebar.min.css') }}" rel="stylesheet">
        <link href="{{ asset('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />
        
    </head>
    <body>
        <div class="container-fluid authentication-bg overflow-hidden">
            <div class="bg-overlay"></div>
            <div class="row align-items-center justify-content-center min-vh-100">
                <div class="col-10 col-md-6 col-lg-4 col-xxl-3">
                    <div class="card card-top-primary card-bottom-primary mb-0">
                        <div class="card-body">
                            <div class="text-center">
                                <a href="{{ route('login') }}" class="logo-dark">
                                    <img src="{{ asset('images/logo.png') }}" alt="" height="80" class="auth-logo logo-dark mx-auto">
                                </a>
                                <a href="{{ route('login') }}" class="logo-dark">
                                    <img src="{{ asset('images/logo.png') }}" alt="" height="80" class="auth-logo logo-light mx-auto">
                                </a>                                
                            </div>
                            <div class="p-2 mt-2">
                                {{ $slot }}
                            </div>
                            <div class="mt-3 text-center">
                                <p>© <script>document.write(new Date().getFullYear())</script> Cool AgriStock.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ asset('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ asset('libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ asset('libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ asset('libs/node-waves/waves.min.js') }}"></script>

        <script src="{{ asset('js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>
