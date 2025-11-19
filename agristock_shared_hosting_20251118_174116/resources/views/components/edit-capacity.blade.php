<div class="modal fade" id="edit-capacity{{ $capacity->id }}">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.capacity', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])</h5>
            </div>
            <form action="{{ route('capacities.update', $capacity->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ $capacity->name }}" name="name" placeholder="Ex: Legumes" required/> <label for="floatingInput">@lang('locale.name')</label>
                        </div>                    
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>