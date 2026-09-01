<x-app-layout>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.nav_sensor_profiles')</h4>
                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-profile"><i class="fas fa-cart-plus"></i> @lang('locale.add')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <table class="table table-sm table-hover table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('locale.sensor_product')</th>
                                <th>@lang('locale.sensor_min_temperature')</th>
                                <th>@lang('locale.sensor_max_temperature')</th>
                                <th>@lang('locale.sensor_min_rh')</th>
                                <th>@lang('locale.sensor_max_rh')</th>
                                <th>@lang('locale.sensor_in_use_at')</th>
                                <th>@lang('locale.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($profiles as $profile)
                                @php
                                    $usedAt = ($inUseAt[$profile->product_id] ?? collect())->pluck('name');
                                    $tempRange = ($profile->min_temperature ?? '—') . '–' . ($profile->max_temperature ?? '—');
                                    $rhRange = ($profile->min_rh ?? '—') . '–' . ($profile->max_rh ?? '—');
                                @endphp
                                <tr>
                                    <td>{{ $profile->product->name ?? '—' }}</td>
                                    <td>{{ $profile->min_temperature ?? '—' }}</td>
                                    <td>{{ $profile->max_temperature ?? '—' }}</td>
                                    <td>{{ $profile->min_rh ?? '—' }}</td>
                                    <td>{{ $profile->max_rh ?? '—' }}</td>
                                    <td>
                                        @forelse (($inUseAt[$profile->product_id] ?? collect()) as $i => $facility)
                                            @if ($i > 0), @endif
                                            <a href="{{ rtrim($facilityDashboardUrl, '/') }}/facilities/{{ $facility['id'] }}" target="_blank" rel="noopener">{{ $facility['name'] }}</a>
                                        @empty
                                            —
                                        @endforelse
                                    </td>
                                    <td>
                                        <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-profile{{ $profile->id }}"><i class="fas fa-edit"></i></a>
                                        <x-edit-sensor-profile :profile="$profile" :facilities="$facilities"></x-edit-sensor-profile>
                                        <a style="display: inline-block; cursor: pointer;" class="btn btn-label-secondary" data-bs-toggle="collapse" data-bs-target="#profile-detail-{{ $profile->id }}" title="@lang('locale.sensor_details')"><i class="fas fa-info-circle"></i></a>
                                    </td>
                                </tr>
                                <tr class="collapse" id="profile-detail-{{ $profile->id }}">
                                    <td colspan="7" class="bg-light-subtle">
                                        <p class="mb-2"><strong>@lang('locale.sensor_details_meaning_label'):</strong> @lang('locale.sensor_details_meaning', ['product' => $profile->product->name ?? '—', 'tempRange' => $tempRange, 'rhRange' => $rhRange])</p>
                                        <p class="mb-2"><strong>@lang('locale.sensor_details_usage_label'):</strong> @lang('locale.sensor_details_usage')</p>
                                        <p class="mb-0"><strong>@lang('locale.sensor_details_location_label'):</strong> {{ $usedAt->isNotEmpty() ? $usedAt->join(', ') : __('locale.sensor_details_nowhere') }}</p>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="new-profile">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.sensor_new_profile')</h5>
                </div>
                <form action="{{ route('sensor-profiles.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">
                            <select class="form-select" name="product_id" required>
                                <option value="" disabled selected>@lang('locale.sensor_product')</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}</option>
                                @endforeach
                            </select>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="number" step="0.1" class="form-control" name="min_temperature" placeholder="Ex: 45"/> <label>@lang('locale.sensor_min_temperature')</label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="number" step="0.1" class="form-control" name="max_temperature" placeholder="Ex: 55"/> <label>@lang('locale.sensor_max_temperature')</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="number" step="0.1" class="form-control" name="min_rh" placeholder="Ex: 10"/> <label>@lang('locale.sensor_min_rh')</label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="number" step="0.1" class="form-control" name="max_rh" placeholder="Ex: 40"/> <label>@lang('locale.sensor_max_rh')</label>
                                    </div>
                                </div>
                            </div>
                            <select class="form-select" name="facility_id">
                                <option value="">@lang('locale.sensor_select_facility')</option>
                                @foreach ($facilities as $facility)
                                    <option value="{{ $facility['id'] }}">{{ $facility['name'] }}</option>
                                @endforeach
                            </select>
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
</x-app-layout>
