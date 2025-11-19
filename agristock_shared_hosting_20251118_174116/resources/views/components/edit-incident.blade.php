<div class="modal fade" id="edit-incident{{ $incident->id }}">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.incident', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('incidents.update', $incident->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <div class="form-floating">
                            <select class="form-select" name="type" aria-label="@lang('locale.type', ['suffix'=>''])" required>
                                @foreach (['INCIDENT SPECIFIQUE', 'INCIDENT GENERAL'] as $item)
                                <option value="{{ $item }}" {{ $item == $incident->type ? 'selected' : '' }}>{{ $item }}</option>
                                @endforeach
                            </select>
                            <label for="type">@lang('locale.type', ['suffix'=>''])</label>
                        </div> 
                        <div>
                            <label for="message" class="form-label">@lang('locale.message')</label> 
                            <textarea class="form-control" name="description" id="description" rows="3" style="resize: none">{{ $incident->message }}</textarea>
                        </div>                   
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>