<x-app-layout>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.nav_sensor_batches')</h4>
                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-batch"><i class="fas fa-cart-plus"></i> @lang('locale.add')</button>
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
                                <th>@lang('locale.sensor_batch_code')</th>
                                <th>Environment</th>
                                <th>@lang('locale.sensor_product')</th>
                                <th>@lang('locale.sensor_customer')</th>
                                <th>@lang('locale.sensor_start_time')</th>
                                <th>@lang('locale.sensor_end_time')</th>
                                <th>@lang('locale.sensor_status')</th>
                                <th>@lang('locale.actions')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($batches as $batch)
                                <tr>
                                    <td>{{ $batch->batch_code }}</td>
                                    <td>{{ $batch->storage->name ?? '—' }}</td>
                                    <td>{{ $batch->product->name ?? '—' }}</td>
                                    <td>{{ $batch->customer->name ?? '—' }}</td>
                                    <td>{{ $batch->start_time->format('Y-m-d H:i') }}</td>
                                    <td>{{ $batch->end_time?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td>
                                        <span class="badge {{ ['in_progress' => 'bg-primary', 'completed' => 'bg-success', 'cancelled' => 'bg-secondary'][$batch->status] }}">
                                            @lang('locale.sensor_' . $batch->status)
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('sensors.show', $batch->storage_id) }}" class="btn btn-label-primary"><i class="fas fa-eye"></i></a>
                                        <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-batch{{ $batch->id }}"><i class="fas fa-edit"></i></a>
                                        <x-edit-sensor-batch :batch="$batch"></x-edit-sensor-batch>
                                        <form action="{{ route('sensor-batches.destroy', $batch->id) }}" method="post" style="display: inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-label-danger" onclick="if(!confirm('Confirmez-Vous cette Suppression ?')) return false"><i class="fa fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-3">—</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="new-batch">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.sensor_new_batch')</h5>
                </div>
                <form action="{{ route('sensor-batches.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" name="batch_code" placeholder="Ex: CAS-2026-0081" required/> <label>@lang('locale.sensor_batch_code')</label>
                            </div>
                            <select class="form-select" name="storage_id" required>
                                <option value="" disabled selected>Environment / Dryer</option>
                                @foreach ($environments as $env)
                                    <option value="{{ $env->id }}">{{ $env->name }}</option>
                                @endforeach
                            </select>
                            <select class="form-select" name="product_id" required>
                                <option value="" disabled selected>@lang('locale.sensor_product')</option>
                                @foreach ($products as $product)
                                    <option value="{{ $product->id }}" @if(!isset($profiles[$product->id])) disabled @endif>
                                        {{ $product->name }} @if(!isset($profiles[$product->id])) (@lang('locale.sensor_no_profile')) @endif
                                    </option>
                                @endforeach
                            </select>
                            <select class="form-select" name="customer_id">
                                <option value="">@lang('locale.sensor_customer')</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                            <div class="form-floating">
                                <input type="datetime-local" class="form-control" name="start_time" value="{{ now()->format('Y-m-d\TH:i') }}" required/> <label>@lang('locale.sensor_start_time')</label>
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
</x-app-layout>
