<div class="modal fade" id="edit-batch{{ $batch->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">{{ $batch->batch_code }}</h5>
            </div>
            <form action="{{ route('sensor-batches.update', $batch->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <select class="form-select" name="status" required>
                            <option value="in_progress" @selected($batch->status === 'in_progress')>@lang('locale.sensor_in_progress')</option>
                            <option value="completed" @selected($batch->status === 'completed')>@lang('locale.sensor_completed')</option>
                            <option value="cancelled" @selected($batch->status === 'cancelled')>@lang('locale.sensor_cancelled')</option>
                        </select>
                        <div class="form-floating">
                            <input type="datetime-local" class="form-control" value="{{ $batch->end_time?->format('Y-m-d\TH:i') }}" name="end_time"/> <label>@lang('locale.sensor_end_time')</label>
                        </div>
                        <div class="form-floating">
                            <input type="text" class="form-control" value="{{ $batch->outcome }}" name="outcome" placeholder="Ex: Good quality"/> <label>@lang('locale.sensor_outcome')</label>
                        </div>
                        <div class="form-floating">
                            <textarea class="form-control" name="notes" style="height:100px">{{ $batch->notes }}</textarea> <label>@lang('locale.sensor_notes')</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button>
                    <button class="btn btn-outline-danger" data-bs-dismiss="modal" type="button">@lang('locale.close') <i class="mdi mdi-close"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>
