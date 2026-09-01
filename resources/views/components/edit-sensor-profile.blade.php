<div class="modal fade" id="edit-profile{{ $profile->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">{{ $profile->product->name ?? '' }}</h5>
            </div>
            <form action="{{ route('sensor-profiles.update', $profile->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" step="0.1" class="form-control" value="{{ $profile->min_temperature }}" name="min_temperature"/> <label>@lang('locale.sensor_min_temperature')</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" step="0.1" class="form-control" value="{{ $profile->max_temperature }}" name="max_temperature"/> <label>@lang('locale.sensor_max_temperature')</label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" step="0.1" min="0" max="100" class="form-control" value="{{ $profile->min_rh }}" name="min_rh"/> <label>@lang('locale.sensor_min_rh')</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="number" step="0.1" min="0" max="100" class="form-control" value="{{ $profile->max_rh }}" name="max_rh"/> <label>@lang('locale.sensor_max_rh')</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button>
                    <button class="btn btn-outline-danger" data-bs-dismiss="modal" type="button">@lang('locale.close') <i class="mdi mdi-close"></i></button>
                </div>
            </form>
            <hr class="m-0">
            <form action="{{ route('sensor-profiles.assign-facility', $profile->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    <label class="form-label mb-1">@lang('locale.sensor_assign_facility')</label>
                    <div class="input-group">
                        <select class="form-select" name="facility_id" required>
                            <option value="" disabled selected>@lang('locale.sensor_select_facility')</option>
                            @foreach ($facilities as $facility)
                                <option value="{{ $facility['id'] }}">{{ $facility['name'] }}</option>
                            @endforeach
                        </select>
                        <button class="btn btn-outline-success">@lang('locale.sensor_assign')</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
