<div class="modal fade" id="edit-product{{ $product->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.product', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('products.update', $product->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ $product->name }}" name="name" placeholder="Ex: Banane" required/> <label for="floatingInput">@lang('locale.name')<span class="text-danger">*</span></label>
                        </div> 
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" class="form-control" value="{{ $product->min_expired_at }}" name="min_expired_at" placeholder="Ex: 10" required/> <label for="floatingInput">Min @lang('locale.expired_at') (@lang('locale.days'))<span class="text-danger">*</span></label>
                                </div> 
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" class="form-control" value="{{ $product->max_expired_at }}" name="max_expired_at" placeholder="Ex: 10"/> <label for="floatingInput">Max @lang('locale.expired_at') (@lang('locale.days'))</label>
                                </div> 
                            </div>
                        </div>
                        <div class="form-floating">
                            <select class="form-select" name="category_id" aria-label="@lang('locale.category', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])" required>
                                @foreach ($categories as $item)
                                <option value="{{ $item->id }}" {{ $item->id == $product->category_id }}>{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <label for="cityId">@lang('locale.category', ['suffix'=>app()->getLocale() == 'en' ? 'y' : '']) <span class="text-danger">*</span></label>
                        </div> 
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>