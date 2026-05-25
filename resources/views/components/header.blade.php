<header id="page-topbar">
    <div class="navbar-header">
        <!-- Logo -->

        <!-- Start Navbar-Brand -->
        <div class="navbar-logo-box">
            <a href="{{ route('dashboard') }}" class="logo logo-dark">
                <span class="logo-sm">
                    <img src="{{ asset('images/logo.png') }}" alt="logo-sm-dark" height="30">
                </span>
                <span class="logo-lg">
                    <img src="{{ asset('images/logo.png') }}" alt="logo-dark" height="60">
                </span>
            </a>

            <a href="{{ route('dashboard') }}" class="logo logo-light">
                <span class="logo-sm">
                    <img src="{{ asset('images/logo.png') }}" alt="logo-sm-light" height="30">
                </span>
                <span class="logo-lg">
                    <img src="{{ asset('images/logo.png') }}" alt="logo-light" height="40">
                </span>
            </a>

            <button type="button" class="btn btn-sm top-icon sidebar-btn" id="sidebar-btn">
                <i class="mdi mdi-menu-open align-middle fs-19"></i>
            </button>
        </div>
        <!-- End navbar brand -->

        <!-- Start menu -->
        <div class="d-flex justify-content-end menu-sm px-3 ms-auto">
            <div class="d-flex align-items-center gap-2">
                <!-- Connectivity status indicator — updated live by resources/js/app.js -->
                <span id="connectivity-status"
                      class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-2 py-1"
                      title="Connected"
                      style="font-size:.72rem">
                    <i class="fas fa-wifi me-1"></i><span class="d-none d-sm-inline">Online</span>
                </span>

                <!-- Start Language Switcher -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-sm top-icon" id="page-header-language-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-globe align-middle"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="page-header-language-dropdown">
                        <a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="{{ route('locale.switch', 'fr') }}">
                            <span class="me-2">🇫🇷</span> @lang('locale.french')
                        </a>
                        <a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="{{ route('locale.switch', 'en') }}">
                            <span class="me-2">🇬🇧</span> @lang('locale.english')
                        </a>
                    </div>
                </div>
                <!-- End Language Switcher -->

                <!-- Start Notification -->
                @if (isGroupAuthorized([1, 2, 3, 4]) && $notifications->count() > 0)
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-sm top-icon" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell align-middle"></i>
                        <span class="btn-marker"><i class="marker marker-dot text-danger"></i><span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg dropdown-menu-md dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                        <div class="p-3 bg-info">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h6 class="text-white m-0"><i class="far fa-bell me-2"></i> @lang('locale.notifications') </h6>
                                </div>
                                <div class="col-auto">
                                    <a class="badge bg-info-subtle text-info"> {{ $notifications->count() }}+</a>
                                </div>
                            </div>
                        </div>
                        <div data-simplebar style="max-height: 230px;">
                            @foreach ($notifications as $item)
                            <a class="text-reset notification-item">
                                <div class="d-flex">
                                    <div class="avatar avatar-xs avatar-label-info me-3">
                                        <span class="rounded fs-16">
                                            <i class="mdi mdi-file-document-outline"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <h6 class="mb-1">{{ $item->name }}</h6>
                                        <div class="fs-12 text-muted">
                                            <p class="mb-0"><i class="mdi mdi-clock-outline"></i> {{ date('d/m:Y H:i:s', strtotime($item->created_at)) }}</p>
                                        </div>
                                    </div>
                                    <i class="mdi mdi-chevron-right align-middle ms-2"></i>
                                </div>
                            </a>
                            @endforeach                            
                        </div>
                        <div class="p-2 border-top">
                            <div class="d-grid">
                                <a class="btn btn-sm btn-link font-size-14 text-center" href="{{ route('claims.index') }}">
                                    <i class="mdi mdi-arrow-right-circle me-1"></i> @lang('locale.see_more')...
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endif                
                <!-- End Notification -->

                <!-- Start Profile -->
                <div class="dropdown d-inline-block">
                    <button type="button" class="btn btn-sm top-icon p-0" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <img class="rounded avatar-2xs p-0" src="{{ asset('images/profile.png') }}" alt="Header Avatar">
                    </button>
                    <div class="dropdown-menu dropdown-menu-wide dropdown-menu-end dropdown-menu-animated overflow-hidden py-0">
                        <div class="card border-0">
                            <div class="card-header bg-info rounded-0">
                                <div class="rich-list-item w-100 p-0">
                                    <div class="rich-list-prepend">
                                        <div class="avatar avatar-label-light avatar-circle">
                                            <div class="avatar-display"><i class="fa fa-user-alt"></i></div>
                                        </div>
                                    </div>
                                    <div class="rich-list-content">
                                        <h3 class="rich-list-title text-white">{{ auth()->user()->name }}</h3>
                                        <span class="rich-list-subtitle text-white">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="grid-nav grid-nav-flush grid-nav-action grid-nav-no-rounded">
                                    <div class="grid-nav-row">
                                        <a href="{{ route('profile') }}" class="grid-nav-item">
                                            <div class="grid-nav-icon"><i class="far fa-address-card"></i></div>
                                            <span class="grid-nav-content">@lang('locale.my_profile')</span>
                                        </a>
                                        <a href="{{ route('logout') }}" class="grid-nav-item text-danger">
                                            <div class="grid-nav-icon text-danger"><i class="fas fa-power-off"></i></div>
                                            <span class="grid-nav-content">@lang('locale.logout')</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Profile -->
            </div>
        </div>
        <!-- End menu -->
    </div>
</header>