<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.storage', ['suffix'=>'s'])</h4>

                @if (isGroupAuthorized([1]))
                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-storage"><i class="fas fa-cart-plus"></i> @lang('locale.add')</button>
                </div>
                @endif
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-footer-callback" class="table table-sm table-hover table-bordered table-striped dt-responsive" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>@lang('locale.name')</th>
                            <th>@lang('locale.capacity', ['suffix'=>app()->getLocale() == 'en' ? 'y' : '']) / @lang('locale.location')</th>
                            <th>@lang('locale.available')</th>
                            @if (isGroupAuthorized([1]))
                            <th>@lang('locale.actions')</th>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($storages as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ switchKgToTonne($item->capacity) }} / {{ $item->location }}</td>
                                <td>
                                    <div class="">
                                        <h6 class="">{{ ($item->available()/($item->capacity == 0 ? 1 : $item->capacity))*100 }}%</h6>
                                        <div class="progress progress-sm">
                                            <div class="progress-bar bg-primary" style="width: {{ ($item->available()/($item->capacity == 0 ? 1 : $item->capacity))*100 }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                @if (isGroupAuthorized([1]))
                                <td>
                                    <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-storage{{ $item->id }}"><i class="fas fa-edit"></i></a>
                                    <x-edit-storage :storage="$item"></x-edit-storage>
                                    <form action="{{ route('storages.destroy', $item->id) }}" method="post" style="display: inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-label-danger" onclick="if(!confirm('Confirmez-Vous cette Suppression ?')) return false"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                                @endif                                
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end col -->
    </div>
    <!-- end row -->

    @if (isGroupAuthorized([1]))
    <div class="modal fade" id="new-storage">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.storage', ['suffix'=>''])</h5>
                </div>
                <form action="{{ route('storages.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="name" name="name" placeholder="Ex: Espace de Stockage à Sec" required/> <label for="floatingInput">@lang('locale.name')</label>
                            </div> 
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="capacity" name="capacity" placeholder="Ex: 100t" required/> <label for="floatingInput">@lang('locale.capacity', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])</label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="location" name="location" placeholder="Ex: 100t" required/> <label for="floatingInput">@lang('locale.location')</label>
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
            </div>
        </div>
    </div>
    @endif    

    @push('scripts')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <script src="{{ asset('js/pages/datatables-advanced.init.js') }}"></script>
    @endpush
</x-app-layout>
