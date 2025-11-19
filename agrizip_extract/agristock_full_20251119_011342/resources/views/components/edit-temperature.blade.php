<div class="modal fade" id="edit-temperature{{ $temperature->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">@lang('locale.temperature', ['suffix'=>''])</h5>
            </div>
            <form action="{{ route('temperatures.update', $temperature->id) }}" method="post">
                @csrf @method('PUT')
                <div class="modal-body">                    
                    <div class="d-grid gap-3">     
                        <div class="form-floating">
                            <select class="form-select" name="storage_id" aria-label="@lang('locale.storage', ['suffix'=>''])" required>
                                @foreach ($storages as $item)
                                <option value="{{ $item->id }}" {{ $temperature->storage_id == $item->id ? 'selected' : '' }}>{{ $item->name." - ".$item->location }}</option>
                                @endforeach
                            </select>
                            <label for="storageId">@lang('locale.storage', ['suffix'=>'']) <span class="text-danger">*</span></label>
                        </div>                    
                        <div class="form-floating">
                            <select class="form-select" name="session" aria-label="@lang('locale.session')" required>
                                @foreach (['PASSAGE 1', 'PASSAGE 2', 'PASSAGE 3'] as $item)
                                <option value="{{ $item }}" {{ $temperature->session == $item ? 'selected' : '' }}>{{ $item }}</option>
                                @endforeach
                            </select>
                            <label for="session">@lang('locale.session') <span class="text-danger">*</span></label>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" class="form-control" value="{{ $temperature->degree }}" name="degree" placeholder="Ex: 37" required/> 
                                    <label for="floatingInput">@lang('locale.temperature', ['suffix'=>'']) (°C) <span class="text-danger">*</span></label>
                                </div> 
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="time" class="form-control" value="{{ $temperature->session_time }}" name="session_time" required/> 
                                    <label for="floatingInput">@lang('locale.session_time')<span class="text-danger">*</span></label>
                                </div> 
                            </div>
                        </div>
                        <div class="form-floating">
                            <select class="form-select" name="type_storage" aria-label="@lang('locale.storage', ['suffix'=>''])" required>
                                @foreach (['STOCKAGE A SEC', 'STOCKAGE REFRIGERE'] as $item)
                                <option value="{{ $item }}" {{ $item == $temperature->type_storage ? 'selected' : '' }}>{{ $item }}</option>
                                @endforeach
                            </select>
                            <label for="storageId">@lang('locale.storage', ['suffix'=>'']) <span class="text-danger">*</span></label>
                        </div> 
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> 
                    <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>