<x-app-layout>
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.billing', ['suffix'=>''])</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('billings.index') }}">@lang('locale.billing', ['suffix'=>'s'])</a></li>
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
                            <div class="col-12">
                                <div class="alert alert-label-success">
                                    <div class="alert-icon"><i class="fas fa-check"></i></div>
                                    <div class="alert-content"><a class="alert-link">@lang('locale.billing', ['suffix'=>''])</a> {{ $billing->ref }}.</div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-grid gap-3 mb-2">
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-2">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ moneyFormat($billing->amount) }}" readonly/> <label>@lang('locale.amount')</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-2">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ moneyFormat($billing->discount) }}" readonly/> <label>@lang('locale.discount')</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-4">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $billing->stock->ref }}" readonly/> <label for="floatingInput">@lang('locale.stock', ['suffix'=>''])</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-4">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $billing->stock->type_storage }}" readonly/> <label for="floatingInput">@lang('locale.type_storage')</label>
                                            </div>                                  
                                        </div>
                                        <div class="col-12 mb-2">
                                            <div class="form-floating">
                                                <input type="text" class="form-control" value="{{ $billing->customer->name }}" readonly/> <label for="floatingInput">@lang('locale.customer', ['suffix'=>''])</label>
                                            </div>
                                        </div>
                                    </div> 
                                </div>
                                
                                <div class="card border mb-2">
                                    <div class="card-header bg-success-subtle">
                                        <h3 class="card-title">@lang('locale.payment', ['suffix'=>'s'])</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-12 table-responsive">
                                                <table class="table table-hover table-bordered table-striped">
                                                    <thead>
                                                        <th>#</th>
                                                        <th>@lang('locale.amount')</th>
                                                        <th>@lang('locale.method')</th>
                                                        <th>@lang('locale.description')</th>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($billing->payments as $item)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ moneyFormat($item->amount) }}</td>
                                                            <td>{{ $item->method }}</td>
                                                            <td>{{ $item->description }}</td>
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
</x-app-layout>
