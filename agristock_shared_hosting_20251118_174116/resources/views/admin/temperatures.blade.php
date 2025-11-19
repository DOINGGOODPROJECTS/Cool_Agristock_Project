<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.temperature', ['suffix'=>'s'])</h4>

                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-temperature"><i class="fas fa-cart-plus"></i> @lang('locale.add')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-footer-callback" class="table table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>@lang('locale.created_at')</th>
                            <th>@lang('locale.type_storage')</th>
                            <th>@lang('locale.storage', ['suffix'=>''])</th>
                            <th>@lang('locale.session')</th>
                            <th>@lang('locale.session_time')</th>
                            <th>@lang('locale.temperature', ['suffix'=>''])</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($temperatures as $item)
                            <x-edit-temperature :temperature="$item" :storages="$storages"></x-edit-temperature>
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ date('d/m/Y H:i:s', strtotime($item->created_at)) }}</td>
                                <td>{{ $item->type_storage }}</td>
                                <td>{{ $item->storage->name." - ".$item->storage->location }}</td>
                                <td>{{ $item->session }}</td>
                                <td>{{ date('H:i', strtotime($item->session_time)) }}</td>
                                <td>{{ $item->degree }} °C</td>
                                <td>
                                    <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-temperature{{ $item->id }}"><i class="fas fa-edit"></i></a>
                                    <form action="{{ route('temperatures.destroy', $item->id) }}" method="post" style="display: inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-label-danger" onclick="if(!confirm('Confirmez-Vous cette Suppression ?')) return false"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div>
    <!-- end row -->

    <div class="modal fade" id="new-temperature">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.temperature', ['suffix'=>''])</h5>
                </div>
                <form action="{{ route('temperatures.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">
                            <div class="form-floating">
                                <select class="form-select" name="storage_id" aria-label="@lang('locale.storage', ['suffix'=>''])" required>
                                    @foreach ($storages as $item)
                                    <option value="{{ $item->id }}">{{ $item->name." - ".$item->location }}</option>
                                    @endforeach
                                </select>
                                <label for="cityId">@lang('locale.storage', ['suffix'=>'']) <span class="text-danger">*</span></label>
                            </div>  
                            <div class="form-floating">
                                <select class="form-select" name="session" aria-label="@lang('locale.session')" required>
                                    @foreach (['PASSAGE 1', 'PASSAGE 2', 'PASSAGE 3'] as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                                <label for="cityId">@lang('locale.session') <span class="text-danger">*</span></label>
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="degree" name="degree" placeholder="Ex: 37" required/> <label for="floatingInput">@lang('locale.temperature', ['suffix'=>'']) (°C) <span class="text-danger">*</span></label>
                                    </div> 
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="time" class="form-control" id="session_time" name="session_time" required/> <label for="floatingInput">@lang('locale.session_time')<span class="text-danger">*</span></label>
                                    </div> 
                                </div>
                            </div>
                            <div class="form-floating">
                                <select class="form-select" name="type_storage" aria-label="@lang('locale.storage', ['suffix'=>''])" required>
                                    @foreach (['STOCKAGE A SEC', 'STOCKAGE REFRIGERE'] as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                                <label for="storageId">@lang('locale.storage', ['suffix'=>'']) <span class="text-danger">*</span></label>
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

    @push('scripts')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <script src="{{ asset('js/pages/datatables-advanced.init.js') }}"></script>
    @endpush
</x-app-layout>
