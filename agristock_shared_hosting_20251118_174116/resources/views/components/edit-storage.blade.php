<div class="modal fade" id="edit-storage{{ $storage->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.storage', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('storages.update', $storage->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ $storage->name }}" name="name" placeholder="Ex: Espace de Stockage à Sec" required/> <label for="floatingInput">@lang('locale.name')</label>
                        </div>        
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control"" value="{{ $storage->capacity }}" name="capacity" placeholder="Ex: 100t" required/> <label for="floatingInput">@lang('locale.capacity', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control"" value="{{ $storage->location }}" name="location" placeholder="Ex: 100t" required/> <label for="floatingInput">@lang('locale.location')</label>
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