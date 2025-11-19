<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta content="Cool AgriStock, Food Storage" name="description" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta content="Cool AgriStock" name="author" />

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="shortcut icon" href="{{ url('images/favicon.ico') }}">

        <script src="{{ url('js/pages/layout.js') }}"></script>

        <link href="{{ url('css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css" />
        <link href="{{ url('css/icons.min.css') }}" rel="stylesheet" type="text/css" />
        <link href="{{ url('libs/simplebar/simplebar.min.css') }}" rel="stylesheet">
        <link href="{{ url('css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css" />        
    </head>
    <body>
        <div class="container-fluid authentication-bg overflow-hidden">
            <div class="bg-overlay"></div>
            <div class="row align-items-center justify-content-center min-vh-100">
                <div class="col-8 mx-auto">
                    <div class="card card-top-primary card-bottom-primary mb-0">
                        <div class="card-body">
                            <div class="text-center">
                                <a href="{{ route('welcome') }}" class="logo-dark">
                                    <img src="{{ url('images/logo.png') }}" alt="" height="80" class="auth-logo logo-dark mx-auto">
                                </a>
                                <a href="{{ route('welcome') }}" class="logo-dark">
                                    <img src="{{ url('images/logo.png') }}" alt="" height="80" class="auth-logo logo-light mx-auto">
                                </a>                                
                            </div>
                            <h4>{{ $claim->name }}</h4>
                            <div class="row">
                                <table class="table table-hover table-striped dt-responsive" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                    <thead>
                                        <th>@lang('locale.created_at')</th>
                                        <th>@lang('locale.customer', ['suffix'=>''])</th>
                                        <th>@lang('locale.storage', ['suffix'=>''])</th>
                                        <th>@lang('locale.status')</th>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ date('d/m/Y H:i:s', strtotime($claim->created_at)) }}</td>
                                            <td>{{ $claim->customer->name }}</td>
                                            <td>{{ $claim->storage->name }}</td>
                                            <td>{{ $claim->status }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="p-2 mt-2">
                                <p class="text-muted" style="text-align: justify">{{ $claim->message }}</p>
                            </div>
                            <div class="mt-3 text-center">
                                <p>© <script>document.write(new Date().getFullYear())</script> Cool AgriStock.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ url('libs/jquery/jquery.min.js') }}"></script>
        <script src="{{ url('libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <script src="{{ url('libs/metismenu/metisMenu.min.js') }}"></script>
        <script src="{{ url('libs/simplebar/simplebar.min.js') }}"></script>
        <script src="{{ url('libs/node-waves/waves.min.js') }}"></script>

        <script src="{{ url('js/app.js') }}"></script>
        @stack('scripts')
    </body>
</html>
