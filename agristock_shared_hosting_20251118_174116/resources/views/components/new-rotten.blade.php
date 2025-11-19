<div class="modal fade" id="new-rotten{{ $detail->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger-subtle">
                <h5 class="modal-title">@lang('locale.rotten', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('rottens.store') }}" method="post">
                <div class="modal-body">
                    @csrf
                    <input type="hidden" name="detail_id" value="{{ $detail->id }}"/>
                    <input type="hidden" name="stock_id" value="{{ $detail->stock->id }}"/>
                    <div class="d-grid gap-3">                       
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" class="form-control" value="{{ $detail->qty }}" name="before_qty" readonly/> <label for="floatingInput">@lang('locale.before_qty') (kg) <span class="text-danger">*</span></label>
                                </div> 
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" class="form-control" name="after_qty" min="0" max="{{ $detail->qty }}" readonly/> <label for="floatingInput">@lang('locale.after_qty') (kg) <span class="text-danger">*</span></label>
                                </div> 
                            </div>                        
                            <div class="col-12 mt-3">
                                <div class="form-floating">
                                    <input type="number" class="form-control" name="qty" placeholder="Ex: 37 | Max: {{ $detail->qty }}" min="1" max="{{ $detail->qty }}" required/> <label for="floatingInput">@lang('locale.qty') (kg) <span class="text-danger">*</span></label>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>