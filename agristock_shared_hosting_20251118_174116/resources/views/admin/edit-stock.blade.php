<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.stock', ['suffix'=>''])</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('stocks.index') }}">@lang('locale.stock', ['suffix'=>'s'])</a></li>
                        <li class="breadcrumb-item active">@lang('locale.edit')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-10 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('stocks.update', $stock->id) }}" method="post" id="form">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-12">
                                <div class="d-grid gap-3 mb-2">
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="type_storage" aria-label="@lang('locale.storage_type')" required>
                                                    @foreach (['STOCKAGE SEC', 'STOCKAGE REFRIGERE'] as $item)
                                                    <option value="{{ $item }}" {{ $item == $stock->type_storage ? 'selected' : '' }}>{{ $item }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="type_storage">@lang('locale.storage_type')</label>
                                            </div>                                
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="qty" name="qty" value="{{ $stock->qty }}" placeholder="Ex: 20" required/> <label for="floatingInput">@lang('locale.qty') (kg)</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="expired_at" name="expired_at" value="{{ $stock->expired_at }}" placeholder="Ex: 30" required/> <label for="floatingInput">@lang('locale.expired_at') (@lang('locale.days'))</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="customer_id" aria-label="@lang('locale.customer', ['suffix'=>''])" required>
                                                    @foreach ($customers as $item)
                                                    <option value="{{ $item->id }}" {{ $stock->customer_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="customerId">@lang('locale.customer', ['suffix'=>''])</label>
                                            </div>                                
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <select class="form-select" name="storage_id" id="storageId" aria-label="@lang('locale.storage', ['suffix'=>''])" required>
                                                    @foreach ($storages as $item)
                                                    <option value="{{ $item->id }}" {{ $stock->storage_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="storageId">@lang('locale.storage', ['suffix'=>''])</label>
                                            </div>
                                        </div>
                                    </div>   
                                </div>
                                
                                <div class="card border mb-2">
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
                                                        <th>@lang('locale.expiration_possible')</th>
                                                        <th>@lang('locale.actions')</th>
                                                    </thead>
                                                    <tbody id="tbody">
                                                        @foreach ($stock->details as $item)
                                                        <tr>
                                                            <td>{{ $item->product->name }}</td>
                                                            <td>{{ $item->qty }}</td>
                                                            <td>{{ $item->container->name }}</td>
                                                            <td>[{{ $item->product->min_expired_at . (!is_null($item->max_expired_at) ? ' - '.$item->max_expired_at : '') }}] @lang('locale.days') => {{ date('d/m/Y H:i:s', strtotime($item->created_at->addDays($item->product->min_expired_at))) }}</td>
                                                            <td class="text-center"><i class="fa fa-trash text-danger" onclick="deleter(this)"></i></td>
                                                            <input type="hidden" name="product_id[]" value="{{ $item->product->id }}" />
                                                            <input type="hidden" name="containers[]" value="{{ $item->container->id }}" />
                                                            <input type="hidden" name="qtys[]" value="{{ $item->qty }}" />
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-grid">
                                    <button class="btn btn-label-primary">@lang('locale.submit')</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> 
    </div>

    @push('scripts')
    <script>
        localStorage.setItem('total', {{ $stock->details->sum('qty') }});
    </script>
    <script src="{{ asset('js/details.js') }}"></script>
    @endpush
</x-app-layout>
