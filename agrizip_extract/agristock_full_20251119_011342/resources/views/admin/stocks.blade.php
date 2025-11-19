<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.stock', ['suffix'=>'s'])</h4>
                @if (isGroupAuthorized([1, 2]))
                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-stock"><i class="fas fa-cart-plus"></i> @lang('locale.new', ['param'=>__('locale.stock', ['suffix'=>''])])</button>
                </div>
                @endif                
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>@lang('locale.created_at')</th>
                            <th>@lang('locale.ref')</th>
                            <th>@lang('locale.customer', ['suffix'=>''])</th>
                            <th>@lang('locale.storage', ['suffix'=>''])</th>
                            <th>@lang('locale.qty')</th>
                            <th>@lang('locale.storage_duration')</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($stocks as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ date('d/m/Y H:i:s', strtotime($item->created_at)) }}</td>
                                <td>{{ $item->ref }}</td>
                                <td>{{ $item->customer->name }}</td>
                                <td>{{ $item->storage->name }}</td>
                                <td>{{ $item->qty }} kg</td>
                                <td class="">{{ date('d/m/Y', strtotime($item->created_at->addDays($item->expired_at))) ." / ".$item->expired_at }} @lang('locale.days')</td>
                                <td>
                                    <a style="display: inline-block" class="btn btn-label-{{ $item->created_at->addDays($item->expired_at) >= now() ? 'success' : 'danger' }}" href="{{ route('stocks.show', $item->id) }}" title="@lang('locale.details')"><i class="fas fa-folder-open"></i></a>
                                    @if (isGroupAuthorized([1, 2]))
                                        @if ($item->qty == 0)
                                        <div class="row icon-demo-content" style="display: inline-block" title="@lang('locale.released')">
                                            <div class="col-2">
                                                <i class="fas fa-check text-primary"></i>
                                            </div>
                                        </div>                                        
                                        @else
                                        <a style="display: inline-block" class="btn btn-label-info" href="{{ route('stocks.edit', $item->id) }}"><i class="fas fa-edit"></i></a>
                                        <a style="display: inline-block" class="btn btn-label-info" href="{{ route('stocks.invoice', $item->id) }}"><i class="fas fa-print"></i></a>
                                        <form action="{{ route('stocks.destroy', $item->id) }}" method="post" style="display: inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-label-danger" onclick="if(!confirm('Confirmez-Vous cette Suppression ?')) return false"><i class="fa fa-trash"></i></button>
                                        </form>
                                        @endif                                    
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div>
    <!-- end row -->
    <form action="{{ route('stocks.store') }}" method="post" id="form">
        <div class="modal fade" id="new-stock">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header bg-primary-subtle">
                        <h5 class="modal-title">@lang('locale.stock', ['suffix'=>''])</h5>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="row">
                            <div class="col-md-8 col-sm-12 col-xs-12 mx-auto">
                                <div class="d-grid gap-3 mb-2">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="type_storage" aria-label="@lang('locale.storage_type')" required>
                                                    @foreach (['STOCKAGE SEC', 'STOCKAGE REFRIGERE'] as $item)
                                                    <option value="{{ $item }}">{{ $item }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="type_storage">@lang('locale.storage_type')</label>
                                            </div>                                
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="qty" name="qty" placeholder="Ex: 20" required/> <label for="floatingInput">@lang('locale.qty') (kg)</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="expired_at" name="expired_at" placeholder="Ex: 30" required/> <label for="floatingInput">@lang('locale.storage_duration') (@lang('locale.days'))</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="customer_id" aria-label="@lang('locale.customer', ['suffix'=>''])" required>
                                                    @foreach ($customers as $item)
                                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="customerId">@lang('locale.customer', ['suffix'=>''])</label>
                                            </div>                                
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="storage_id" aria-label="@lang('locale.storage', ['suffix'=>''])" id="storageId" required>
                                                    @foreach ($storages as $item)
                                                    <option value="{{ $item->id }}" accesskey="{{ $item->available() }}">{{ $item->name." - ".$item->location." - Restant: ".$item->available()."Kg" }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="storageId">@lang('locale.storage', ['suffix'=>''])</label>
                                            </div>
                                        </div>
                                    </div>   
                                </div>
                                
                                <div class="card border">
                                    <div class="card-header bg-primary-subtle">
                                        <h3 class="card-title">@lang('locale.details')</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            <div class="row">
                                                <div class="col-md-4 col-sm-4 col-xs-12">
                                                    <div class="form-floating">
                                                        <select class="form-select form-select-sm" id="container" aria-label="@lang('locale.container', ['suffix'=>''])" required>
                                                            @foreach ($containers as $item)
                                                            <option value="{{ $item->id }}">{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label for="container">@lang('locale.capacity', ['suffix'=>''])</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 col-sm-4 col-xs-12">
                                                    <div class="form-floating">
                                                        <select class="form-select form-select-sm" id="product_name" aria-label="@lang('locale.product', ['suffix'=>''])" required>
                                                            @foreach ($products as $item)
                                                            <option value="{{ $item->id }}" title="{{ $item->min_expired_at }}">{{ $item->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <label for="productId">@lang('locale.product', ['suffix'=>''])</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-4 col-sm-4 col-xs-12">
                                                    <div class="input-group">
                                                        <div class="form-floating">
                                                            <input type="number" id="product_qty" class="form-control" placeholder="@lang('locale.qty')"> 
                                                            <label for="product_qty">@lang('locale.qty') (kg)</label>
                                                        </div>
                                                        <button class="btn btn-label-primary btn-icon" type="button" id="new-row"><i class="fa fa-plus-circle"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-12">
                                                <table class="table table-hover table-bordered table-striped">
                                                    <thead>
                                                        <th>@lang('locale.product', ['suffix'=>''])</th>
                                                        <th>@lang('locale.qty') (kg)</th>
                                                        <th>@lang('locale.capacity', ['suffix'=>''])</th>
                                                        <th>@lang('locale.expiration_possible') (@lang('locale.days'))</th>
                                                        <th>@lang('locale.actions')</th>
                                                    </thead>
                                                    <tbody id="tbody"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> 
                        <button class="btn btn-outline-danger" data-bs-dismiss="modal" type="button">@lang('locale.close') <i class="mdi mdi-close"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    @push('scripts')
    <!-- Required datatable js -->
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- buttons examples -->
    <script src="{{ asset('libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('js/pages/datatables-extension.init.js') }}"></script>
    <script>
        localStorage.setItem('total', 0);
    </script>
    <script src="{{ asset('js/details.js') }}"></script>
    @endpush
</x-app-layout>
