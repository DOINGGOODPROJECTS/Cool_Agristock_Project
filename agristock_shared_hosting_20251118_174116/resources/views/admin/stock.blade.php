<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.stock', ['suffix'=>''])</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('stocks.index') }}">@lang('locale.stock', ['suffix'=>'s'])</a></li>
                        <li class="breadcrumb-item active">@lang('locale.show')</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 col-sm-12 col-xs-12 mx-auto">
            <div class="card">
                <div class="card-body">
                        <div class="row">
                            @if ($stock->qty == 0)
                            <div class="col-12">
                                <div class="alert alert-label-success">
                                    <div class="alert-icon"><i class="fas fa-check"></i></div>
                                    <div class="alert-content"><a class="alert-link">@lang('locale.stock', ['suffix'=>''])</a> @lang('locale.released').</div>
                                </div>
                            </div>
                            @endif
                            <div class="col-12">
                                <div class="d-grid gap-3 mb-2">
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="qty" name="qty" value="{{ $stock->qty + $stock->releases->sum('qty') }}" readonly/> <label for="floatingInput">@lang('locale.qty') (kg)</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12">
                                            <div class="form-floating">
                                                <input type="number" class="form-control" id="expired_at" name="expired_at" value="{{ $stock->expired_at }}" readonly/> <label for="floatingInput">@lang('locale.storage_duration') (@lang('locale.days'))</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $stock->storage->name }}" readonly/> <label for="floatingInput">@lang('locale.storage', ['suffix'=>''])</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $stock->type_storage }}" readonly/> <label for="floatingInput">@lang('locale.type_storage')</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-12">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $stock->customer->name }}" readonly/> <label for="floatingInput">@lang('locale.customer', ['suffix'=>''])</label>
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                                
                                <div class="card border mb-2">
                                    <div class="card-header bg-success-subtle">
                                        <h3 class="card-title">@lang('locale.details')</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 table-responsive">
                                                <table class="table table-hover table-bordered table-striped">
                                                    <thead>
                                                        <th>@lang('locale.product', ['suffix'=>''])</th>
                                                        <th>@lang('locale.qty') (kg)</th>
                                                        <th>@lang('locale.capacity', ['suffix'=>''])</th>
                                                        <th>@lang('locale.expiration_possible')</th>
                                                        <th>@lang('locale.actions')</th>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($stock->details as $item)
                                                        <tr>
                                                            <td>{{ $item->product->name }}</td>
                                                            <td>{{ $item->qty }}</td>
                                                            <td>{{ $item->container->name }}</td>
                                                            <td>[{{ $item->product->min_expired_at . (!is_null($item->max_expired_at) ? ' - '.$item->max_expired_at : '') }}] @lang('locale.days') => {{ date('d/m/Y H:i:s', strtotime($item->created_at->addDays($item->product->min_expired_at))) }}</td>
                                                            <td class="text-center">
                                                                @if ($stock->qty == 0)
                                                                <div class="text-primary text-italic">@lang('locale.released')</div>
                                                                @else
                                                                    @if ($item->qty > 0)
                                                                    <a style="display: inline-block" class="btn btn-label-success" title="@lang('locale.release', ['suffix'=>''])" data-bs-toggle="modal" data-bs-target="#new-release{{ $item->id }}"><i class=" fas fa-trash-restore-alt"></i></a>
                                                                    <x-new-release :detail="$item"></x-new-release>
                                                                    <a style="display: inline-block" class="btn btn-label-danger" title="@lang('locale.rotten', ['suffix'=>''])" data-bs-toggle="modal" data-bs-target="#new-rotten{{ $item->id }}"><i class="fas fa-trash-alt"></i></a>
                                                                    <x-new-rotten :detail="$item"></x-new-rotten>
                                                                    @endif
                                                                @endif                                                                
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div> 
    </div>

    @push('scripts')
        <script src="{{ asset('js/qty.js') }}"></script>
    @endpush
</x-app-layout>
