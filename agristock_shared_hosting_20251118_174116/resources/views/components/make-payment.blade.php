<div class="modal fade" id="make-payment{{ $billing->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.payment', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('payments.store') }}" method="post">
                @csrf
                <input type="hidden" name="billing_id" value="{{ $billing->id }}" />
                <input type="hidden" name="customer_id" value="{{ $billing->customer_id }}" />
                <input type="hidden" name="stock_id" value="{{ $billing->stock_id }}" />
                <div class="modal-body">
                    <div class="d-grid gap-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" name="location" placeholder="Ex: Djorobité" required/> <label for="floatingInput">@lang('locale.location')</label>
                        </div> 
                        <div class="row">
                            <div class="col-12 d-grid gap-3">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="{{ $billing->amount-$billing->discount-$billing->payments->sum('amount') }}" name="amount" placeholder="Ex: 10000" required/> <label for="amount">@lang('locale.amount') (XOF)</label>
                                </div>  
                                <div class="form-floating">
                                    <select class="form-select" name="method" aria-label="@lang('locale.method')" required>
                                        @foreach (['CASH', 'MOBILE MONEY', 'CREDIT CARD', 'BANK TRANSFER'] as $item)
                                        <option value="{{ $item }}">{{ $item }}</option>
                                        @endforeach
                                    </select>
                                    <label for="method">@lang('locale.method')</label>
                                </div>  
                                <textarea class="form-control autosize" placeholder="@lang('locale.description')..." style="overflow: hidden; overflow-wrap: break-word; resize: none; text-align: start; height: 43.3333px;"></textarea>                              
                            </div>
                        </div>                        
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>