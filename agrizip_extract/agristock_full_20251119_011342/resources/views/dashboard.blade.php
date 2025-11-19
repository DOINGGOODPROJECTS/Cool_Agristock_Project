<x-app-layout>
    @push('links')
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
    <link rel="stylesheet" type="text/css" href="{{ asset('css/dashboard-responsive.css') }}"/>
    @endpush

    @php
        $totalCustomers = $customers->count();
        $totalStorages = $storages->count();
        $totalStockQty = $stocks->sum('qty');
        $todayBillings = $billings->filter(fn($item) => optional($item->created_at)->isToday());
        $latestTemperature = optional($temperatures->where('created_at', date('Y-m-d'))->sortByDesc('id')->first());
        $maxTemperature = optional($temperatures->sortByDesc('degree')->first());
        $minTemperature = optional($temperatures->sortBy('degree')->first());
        $averagePayment = $payments->count() ? $payments->avg('amount') : 0;
        $selectionDataset = $stocks->map(function ($item) {
            $expiresOn = optional($item->created_at)->addDays($item->expired_at);
            return [
                'id' => $item->id,
                'customer_id' => $item->customer_id,
                'customer' => optional($item->customer)->name,
                'storage_id' => $item->storage_id,
                'storage' => optional($item->storage)->name,
                'ref' => $item->ref,
                'billing' => optional($item->billing)->ref,
                'qty' => $item->qty,
                'unit' => __('locale.qty'),
                'expires_on' => $expiresOn ? $expiresOn->format('d/m/Y') : null,
                'expires_in' => $item->expired_at,
                'status' => $item->qty === 0 ? __('locale.released') : ($expiresOn && $expiresOn->isPast() ? __('Expired') : __('Active')),
            ];
        })->values();
    @endphp

    <div class="dashboard-hero card gradient-card text-white border-0 mb-4">
        <div class="hero-overlay-layer"></div>
        <div class="row g-4 align-items-center hero-shell">
            <div class="col-lg-8">
                <div class="hero-intro">
                    <div class="hero-kicker text-uppercase mb-2">
                        <span>Operations pulse</span>
                        <span class="hero-dot"></span>
                        <span>{{ now()->format('H:i') }}</span>
                    </div>
                    <h1 class="hero-title mb-3">
                        @lang('locale.welcome'), <span>{{ auth()->user()->name }}</span>
                    </h1>
                    <p class="hero-text mb-4">
                        @lang('locale.text_dashboard') — track billings, cold-room capacity, and customer commitments in real time from any device.
                    </p>
                </div>
                <div class="hero-metrics mt-4">
                    <div class="hero-metric">
                        <p class="label">@lang('locale.customer', ['suffix'=>'s'])</p>
                        <h4>{{ $totalCustomers }}</h4>
                    </div>
                    <div class="hero-metric">
                        <p class="label">@lang('locale.storage', ['suffix'=>'s'])</p>
                        <h4>{{ $totalStorages }}</h4>
                    </div>
                    <div class="hero-metric">
                        <p class="label">@lang('locale.stock', ['suffix'=>'s'])</p>
                        <h4>{{ number_format($totalStockQty) }} kg</h4>
                    </div>
                </div>
                <div class="hero-tabs mt-3">
                    <button class="hero-pill-btn active">@lang('locale.stock', ['suffix'=>'s'])</button>
                    <button class="hero-pill-btn">@lang('locale.billing', ['suffix'=>'s'])</button>
                    <button class="hero-pill-btn">@lang('locale.customer', ['suffix'=>'s'])</button>
                </div>
                <div class="hero-actions mt-4">
                    <a href="{{ route('stocks.index') }}" class="btn btn-hero-primary btn-rounded me-2">@lang('locale.stock', ['suffix'=>'s'])</a>
                    <a href="{{ route('billings.index') }}" class="btn btn-hero-ghost btn-rounded">@lang('locale.billing', ['suffix'=>'s'])</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="hero-side-card">
                    <div class="hero-meta text-end">
                        <p class="hero-date mb-1">{{ now()->format('l, d F Y') }}</p>
                        <p class="hero-clock mb-3">{{ now()->format('h:i A') }}</p>
                        <span class="hero-pill">@lang('locale.dashboard')</span>
                    </div>
                    <div class="hero-snapshot mt-4">
                        <p class="text-uppercase small mb-2">{{ __('Today') }}</p>
                        <div class="hero-snapshot-value">{{ moneyFormat($todayBillings->sum('amount')) }}</div>
                        <p class="text-white-75 mb-3">{{ $todayBillings->count() }} @lang('locale.billing', ['suffix'=>'s'])</p>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="{{ route('billings.index') }}" class="btn btn-sm btn-hero-secondary text-white fw-semibold px-3">{{ __('Review bills') }}</a>
                            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-hero-ghost text-white px-3">{{ __('Payments') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card shadow-sm border-0 dashboard-filter mb-4">
        <div class="card-body">
            <div class="row g-3 g-md-4 align-items-end">
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select class="form-select" id="customerId" aria-label="@lang('locale.customer', ['suffix'=>''])" required>
                            <option value="">@lang('locale.select')</option>
                            @foreach ($customers as $item)
                            <option value="{{ $item->id }}" {{ isGroupAuthorized([5, 6, 7, 8]) ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                        <label for="customerId">@lang('locale.customer', ['suffix'=>''])</label>
                    </div>                                
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-floating">
                        <select class="form-select" aria-label="@lang('locale.storage', ['suffix'=>''])" id="storageId" required>
                            <option value="">@lang('locale.select')</option>
                            @foreach ($storages as $item)
                            <option value="{{ $item->id }}">{{ $item->name." - ".$item->location." - Restant: ".$item->available()."Kg" }}</option>
                            @endforeach
                        </select>
                        <label for="storageId">@lang('locale.storage', ['suffix'=>''])</label>
                    </div>
                </div>
            </div> 
            <div class="row mt-3">
                <div class="col-12 text-end">
                    <button id="viewDetailsBtn" class="btn btn-hero-secondary btn-rounded" disabled>
                        {{ __('View details') }}
                    </button>
                </div>
            </div>
        </div>
    </div> 

    <div id="selection-details" class="selection-details card border-0 shadow-sm d-none">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ __('Selection Insights') }}</h5>
                <p class="mb-0 text-muted small" id="selection-details-subtitle">{{ __('Choose a customer and storage zone, then click view details.') }}</p>
            </div>
            <span class="badge bg-soft-info text-info fw-semibold" id="selection-details-count">0</span>
        </div>
        <div class="card-body">
            <div class="details-empty text-muted text-center">
                {{ __('No details to show yet. Make a selection above.') }}
            </div>
            <div class="details-body d-none"></div>
        </div>
    </div>

    <div id="content">
        <div class="row g-4 align-items-start">
            <div class="col-12">
                <div class="metrics-row">
                    <div class="metric-tile">
                        <div class="card stat-card accent-bills shadow-none border-0">
                            <div class="card-body">
                                <div class="stat-card-header">
                                    <span class="stat-label">@lang('locale.billing', ['suffix'=>'s'])</span>
                                    <span class="stat-chip">{{ $billings->count() }} @lang('locale.total')</span>
                                </div>
                                <div class="stat-body">
                                    <h3 class="stat-value mb-2">{{ moneyFormat($billings->sum('amount')) }}</h3>
                                    <p class="stat-subtitle mb-3">{{ __('Open invoices across customers') }}</p>
                                    <div class="stat-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <a href="{{ route('billings.index') }}" class="stat-link">@lang('locale.billing', ['suffix'=>'s'])</a>
                                        <span class="stat-trend">
                                            <i class="mdi mdi-arrow-top-right me-1"></i>{{ $todayBillings->count() }} {{ __('today') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="stat-card-icon">
                                    <span class="icon-circle icon-soft-danger">
                                        <i class="fas fa-file-invoice-dollar"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="metric-tile">
                        <div class="card stat-card accent-payments shadow-none border-0">
                            <div class="card-body">
                                <div class="stat-card-header">
                                    <span class="stat-label">@lang('locale.payment', ['suffix'=>'s'])</span>
                                    <span class="stat-chip">{{ $payments->count() }} {{ __('logs') }}</span>
                                </div>
                                <div class="stat-body">
                                    <h3 class="stat-value mb-2">{{ moneyFormat($payments->sum('amount')) }}</h3>
                                    <p class="stat-subtitle mb-3">{{ __('Cleared & pending payment entries') }}</p>
                                    <div class="stat-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <a href="{{ route('payments.index') }}" class="stat-link">@lang('locale.payment', ['suffix'=>'s'])</a>
                                        <span class="stat-trend">
                                            <i class="mdi mdi-arrow-top-right me-1"></i>{{ moneyFormat($averagePayment) }} {{ __('avg') }}
                                        </span>
                                    </div>
                                </div>
                                <div class="stat-card-icon">
                                    <span class="icon-circle icon-soft-success">
                                        <i class="fas fa-wallet"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="metric-tile">
                        <div class="card stat-card accent-stocks shadow-none border-0">
                            <div class="card-body">
                                <div class="stat-card-header">
                                    <span class="stat-label">@lang('locale.stock', ['suffix'=>'s'])</span>
                                    <span class="stat-chip">{{ $stocks->count() }} {{ __('entries') }}</span>
                                </div>
                                <div class="stat-body">
                                    <h3 class="stat-value mb-2">{{ number_format($stocks->sum('qty')) }} kg</h3>
                                    <p class="stat-subtitle mb-3">{{ __('Tracked across all storages') }}</p>
                                    <div class="stat-footer d-flex align-items-center justify-content-between flex-wrap gap-2">
                                        <a href="{{ route('stocks.index') }}" class="stat-link">{{ __('View storage map') }}</a>
                                        <span class="stat-trend">
                                            @if (isGroupAuthorized([5, 6, 7, 8]))
                                                <button class="btn btn-sm btn-soft-info">@lang('locale.delivery', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])</button>
                                            @else
                                                {{ $releases->count() }} @lang('locale.released')
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                <div class="stat-card-icon">
                                    <span class="icon-circle icon-soft-info">
                                        <i class="fas fa-warehouse"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- end row -->

                <div class="row g-4 align-items-stretch">
                    <div class="col-12 col-lg-8">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-header bg-success-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h3 class="card-title mb-0">
                                        <i class="fas fa-cart-plus fs-14 text-muted"></i> 10 @lang('locale.last_stocks')
                                    </h3>
                                    <p class="text-muted mb-0 small">{{ __('Live sync with storage releases') }}</p>
                                </div>
                                <a href="{{ route('stocks.index') }}" class="btn btn-sm btn-success px-3 text-white">{{ __('All stocks') }}</a>
                            </div>
                            <div class="card-body">
                                <div class="table-legends mb-3">
                                    <span class="legend-item"><span class="legend-dot bg-primary"></span>{{ __('Within SLA') }}</span>
                                    <span class="legend-item"><span class="legend-dot bg-danger"></span>{{ __('Expired / attention') }}</span>
                                </div>
                                <div class="table-responsive table-modern-wrapper">
                                    @php $stocksPreviewLimit = 4; @endphp
                                    <table class="table table-modern align-middle" id="last-stocks-table" data-preview-limit="{{ $stocksPreviewLimit }}">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col">#</th>
                                                <th scope="col">@lang('locale.customer', ['suffix'=>''])</th>
                                                <th scope="col">@lang('locale.ref')</th>
                                                <th scope="col">@lang('locale.billing', ['suffix'=>''])</th>
                                                <th scope="col">@lang('locale.qty')</th>
                                                <th scope="col">@lang('locale.expired_at')</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($stocks as $item)
                                            <tr class="table-row{{ $loop->iteration > $stocksPreviewLimit ? ' table-row-hidden' : '' }}">
                                                <td data-label="#">{{ $loop->iteration }}</td>
                                                <td data-label="@lang('locale.customer', ['suffix'=>''])">{{ $item->customer->name }}</td>
                                                <td data-label="@lang('locale.ref')">{{ $item->ref }}</td>
                                                <td data-label="@lang('locale.billing', ['suffix'=>''])">{{ $item->billing->ref }}</td>
                                                <td data-label="@lang('locale.qty')">{{ $item->qty }} kg</td>
                                                <td class="text-{{ $item->created_at->addDays($item->expired_at) >= now() ? 'primary' : 'danger' }}" data-label="@lang('locale.expired_at')">
                                                    @if ($item->qty == 0)
                                                        <div class="text-primary text-italic">@lang('locale.released')</div>
                                                    @else
                                                        {{ date('d/m/Y', strtotime($item->created_at->addDays($item->expired_at))) ." / ".$item->expired_at }} @lang('locale.days')
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @if ($stocks->count() > $stocksPreviewLimit)
                                <div class="text-center mt-3">
                                    <button class="btn btn-soft-info px-4 view-more-toggle" data-viewmore-target="#last-stocks-table" data-state="collapsed">
                                        {{ __('View more') }}
                                    </button>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card h-100 shadow-sm border-0">
                            <div class="card-header card-header-bordered bg-info-subtle d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div>
                                    <h3 class="card-title mb-0">
                                        <i class="fas fa-newspaper"></i>
                                        @lang('locale.flash')
                                    </h3>
                                    <p class="text-muted mb-0 small">{{ __('Stay ahead of incidents & alerts') }}</p>
                                </div>
                                <button class="btn btn-sm btn-outline-info px-3" id="refresh-incidents">{{ __('Refresh feed') }}</button>
                            </div>
                            <div class="card-body">
                                <div class="slider responsive flash-slider">
                                    @foreach ($incidents as $item)
                                    <div class="card mb-0 text-center">
                                        <h6>{{ $item->type }}</h6>
                                        <p class="text-muted" style="text-align: justify">
                                            {{ $item->description ?? 'No Description' }}
                                        </p>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end row -->

        <div class="row g-4 align-items-stretch second-row">
            <div class="col-12 col-xl-8 order-2 order-xl-1">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header card-header-bordered d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="card-icon text-muted"><i class="fas fa-money-bill fs14"></i></div>
                            <div>
                                <h3 class="card-title mb-0">5 @lang('locale.billing', ['suffix'=>'s'])</h3>
                                <small class="text-muted">{{ __('Latest customer activity') }}</small>
                            </div>
                        </div>
                        <div class="btn-group btn-group-sm list-range" role="group">
                            <button type="button" class="btn btn-outline-secondary active" data-range="today">{{ __('Today') }}</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="week">{{ __('Week') }}</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="month">{{ __('Month') }}</button>
                        </div>
                    </div>
                    <div class="card-body rich-list-wrapper" data-simplebar>
                        <div class="rich-list rich-list-flush mb-0">
                            @forelse ($billings as $item)
                            <div class="flex-column align-items-stretch">
                                <div class="rich-list-item">
                                    <div class="rich-list-prepend">
                                        <div class="avatar avatar-xs">
                                            <div class=""><img src="https://img.icons8.com/cotton/100/stack-of-money--v3.png" alt="Avatar image" class="avatar-2xs" /></div>
                                        </div>
                                    </div>
                                    <div class="rich-list-content">
                                        <h4 class="rich-list-title mb-1">@lang('locale.billing', ['suffix'=>'']) : {{ $item->ref }} | @lang('locale.customer', ['suffix'=>'']) : {{ $item->customer->name }}</h4>
                                        <p class="rich-list-subtitle mb-0">{{ date('d/m/Y H:i:s', strtotime($item->created_at)) }} | @lang('locale.amount') : {{ moneyFormat($item->amount) }} | @lang('locale.discount') : {{ moneyFormat($item->discount) }}</p>
                                    </div>
                                    <div class="rich-list-append"><a class="btn btn-sm btn-label-{{ $item->payments->sum('amount') == $item->amount ? 'success' : (!$item->delayed_at->isFuture() ? 'danger' : 'warning') }}" href="{{ route('stocks.show', $item->stock_id) }}">@lang('locale.stock', ['suffix'=>''])</a></div>
                                </div>
                            </div>
                            @empty
                            <p class="text-muted text-center">@lang('locale.empty')</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-xl-4 order-1 order-xl-2">
                <div class="card temperature-panel shadow-sm border-0 h-100">
                    <div class="card-header justify-content-between">
                        <div class="card-icon text-muted"><i class="fas fa-temperature-low fs-14"></i></div>
                        <h4 class="card-title">@lang('locale.temperature', ['suffix'=>'s'])</h4>
                        <div class="card-addon dropdown"></div>
                    </div>
                    <div class="card-body">
                        <div class="border-bottom hstack justify-content-center gap-4 pb-3 text-center">
                            @if (is_null($latestTemperature))
                                <p class="text-muted text-danger mb-0">Aucune température recueillie</p>
                            @else
                                <div>
                                    <span class="text-primary fs-22 me-2"><i class="fas fa-thermometer-half"></i></span>
                                    <h4 class="display-6 mb-0">{{ $latestTemperature->degree }}°C</h4>
                                    <p class="text-muted mb-0">@lang('locale.cold_storage')</p>
                                    <small class="text-muted">
                                        {{ __('Last update') }}:
                                        {{ optional($latestTemperature->created_at)->format('H:i') ?? __('N/A') }}
                                    </small>
                                </div>
                            @endif
                        </div>
                        <div class="border-bottom hstack justify-content-center gap-4 py-3">
                            <div class="text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <h4 class="display-6 mb-0" id="ambient-temp"></h4>
                                </div>
                                <p class="text-muted mb-0">@lang('locale.ambient_temperature')</p>
                            </div>
                        </div>
                        <div class="border-bottom py-3">
                            <div class="temperature-glance d-flex justify-content-between">
                                <div>
                                    <p class="text-muted mb-1">{{ __('High') }}</p>
                                    <h5 class="mb-0">{{ optional($maxTemperature)->degree ?? '—' }}°C</h5>
                                </div>
                                <div>
                                    <p class="text-muted mb-1">{{ __('Low') }}</p>
                                    <h5 class="mb-0">{{ optional($minTemperature)->degree ?? '—' }}°C</h5>
                                </div>
                                <div>
                                    <p class="text-muted mb-1">{{ __('Readings') }}</p>
                                    <h5 class="mb-0">{{ $temperatures->count() }}</h5>
                                </div>
                            </div>
                        </div>
                        <div class="border-bottom hstack justify-content-center gap-4 py-3 temperature-states">
                            @foreach ($temperatures as $item)
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h5 class="fs-6 mb-0"><i class="fas fa-temperature-high text-{{ $item->degree > 37 ? 'danger' : 'primary' }} me-2"></i> {{ $item->session }}</h5>
                                <p class="text-muted mb-0">{{ $item->degree }}°C</p>
                            </div> 
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 chart-row">
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 chart-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="card-icon text-muted"><i class="fas fa-money fs-14"></i></div>
                            <h4 class="card-title mb-0">@lang('locale.payment', ['suffix'=>'s']) & @lang('locale.billing', ['suffix'=>''])</h4>
                            <small class="text-muted">{{ __('Toggle range to compare trends') }}</small>
                        </div>
                        <div class="btn-group btn-group-sm chart-range" role="group" data-target="graphpayment">
                            <button type="button" class="btn btn-outline-primary active" data-range="7d">7d</button>
                            <button type="button" class="btn btn-outline-primary" data-range="30d">30d</button>
                            <button type="button" class="btn btn-outline-primary" data-range="90d">90d</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="hstack justify-content-center gap-4 pb-3">
                            <div id="graphpayment" class="chart-canvas"></div>
                        </div>
                    </div>
                </div>
            </div> 
            <div class="col-12 col-lg-6">
                <div class="card shadow-sm border-0 chart-card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="card-icon text-muted"><i class="fas fa-temperature-low fs-14"></i></div>
                            <h4 class="card-title mb-0">@lang('locale.temperature', ['suffix'=>'s'])</h4>
                            <small class="text-muted">{{ __('Minute-by-minute warehouse readings') }}</small>
                        </div>
                        <div class="btn-group btn-group-sm chart-range" role="group" data-target="graphtemperature">
                            <button type="button" class="btn btn-outline-info active" data-range="today">{{ __('Today') }}</button>
                            <button type="button" class="btn btn-outline-info" data-range="week">{{ __('Week') }}</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="hstack justify-content-center gap-4 pb-3">
                            <div id="graphtemperature" class="chart-canvas"></div>
                        </div>
                    </div>
                </div>
            </div> 
            <div class="col-12">
                <div class="card shadow-sm border-0 chart-card">
                    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="card-icon text-muted"><i class="fas fa-warehouse fs-14"></i></div>
                            <h4 class="card-title mb-0">{{ __('Storage Utilisation') }}</h4>
                            <small class="text-muted">{{ __('Hover the chart to inspect site occupancy') }}</small>
                        </div>
                        <div class="btn-group btn-group-sm chart-range" role="group" data-target="graphstorage">
                            <button type="button" class="btn btn-outline-secondary active" data-range="capacity">{{ __('Capacity') }}</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="temperature">@lang('locale.temperature', ['suffix'=>'s'])</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="hstack justify-content-center gap-4 pb-3">
                            <div id="graphstorage" class="chart-canvas"></div>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
        <!-- end row -->
    </div>

    @push('scripts')
    <script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ asset('js/pages/dashboard.init.js') }}"></script>
    <script>
        window.dashboardSelectionData = @json($selectionDataset);
        window.dashboardTranslations = {
            noResults: "{{ __('No records found for this selection.') }}"
        };
    </script>
    <script src="{{ asset('js/filter.js') }}"></script>

    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.6.0/echarts.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.slider').slick({
                autoplay: true,                 // Enables auto-scrolling
                autoplaySpeed: 2500,            // Sets the speed of auto-scrolling (in ms)
                arrows: false,                  // Disables navigation arrows
                dots: true,                     // Adds dots for pagination
                infinite: true,                 // Enables infinite loop
                speed: 500,                     // Speed of sliding transition
                adaptiveHeight: true,
                pauseOnHover: true,
                pauseOnFocus: true,
                mobileFirst: true,
                swipeToSlide: true,
                touchThreshold: 8,
                cssEase: 'ease-out',
                slidesToShow: 1,
                responsive: [
                    {
                        breakpoint: 1200,
                        settings: {
                            slidesToShow: 1
                        }
                    }
                ]
            });

            const wireToggleGroups = (selector) => {
                document.querySelectorAll(selector).forEach((group) => {
                    group.querySelectorAll('button').forEach((btn) => {
                        btn.addEventListener('click', () => {
                            group.querySelectorAll('button').forEach((el) => el.classList.remove('active'));
                            btn.classList.add('active');
                        });
                    });
                });
            };

            wireToggleGroups('.chart-range');
            wireToggleGroups('.list-range');

            $('#refresh-incidents').on('click', function() {
                $('.flash-slider').slick('slickGoTo', 0);
            });

            $('[data-viewmore-target]').each(function() {
                var button = $(this);
                var target = $(button.data('viewmore-target'));

                if (!target.length) {
                    return;
                }

                var limit = parseInt(target.data('preview-limit')) || 4;
                var rows = target.find('tbody tr');
                var hiddenRows = rows.slice(limit);

                if (!hiddenRows.length) {
                    button.hide();
                    return;
                }

                const toggleRows = (expand) => {
                    if (expand) {
                        hiddenRows.removeClass('table-row-hidden');
                        button.attr('data-state', 'expanded').text('{{ __('Show less') }}');
                    } else {
                        hiddenRows.addClass('table-row-hidden');
                        button.attr('data-state', 'collapsed').text('{{ __('View more') }}');
                        target[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                };

                button.on('click', function() {
                    const isExpanded = button.attr('data-state') === 'expanded';
                    toggleRows(!isExpanded);
                });
            });

            var cityName = "Abidjan"; // Replace with your city name
            var apiKey = "{{ env('OPENWEATHER_KEY') }}"; // Replace with your API key

            fetch(`https://api.openweathermap.org/data/2.5/weather?q=${cityName}&appid=${apiKey}&units=metric`)
            .then(response => response.json())
            .then(data => {
            console.log(data);
                // Here you can process the data and display it as needed
                var weather = data.weather[0].description;
                var temperature = data.main.temp;
                $('#ambient-temp').text(temperature+'°C');
                // console.log(`Weather: ${weather}, Temperature: ${temperature}`);
            })
            .catch(error => {
                console.error("Error fetching weather data: ", error);
            });
        });
    </script>
    <x-_temperature :temperatures="$temperatures"></x-_temperature>
    <x-_payment :billings="$billings"></x-_payment>
    <x-_storage :storages="$storages"></x-_storage>
    @endpush
</x-app-layout>
