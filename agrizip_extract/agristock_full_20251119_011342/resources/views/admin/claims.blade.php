<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.claim', ['suffix'=>'s'])</h4>    
                @if (isGroupAuthorized([4, 5, 6, 7, 8]))
                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-claim"><i class="fas fa-bell"></i> @lang('locale.add')</button>
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
                    <table id="datatable-buttons" class="table table-sm table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>@lang('locale.created_at')</th>
                            <th>@lang('locale.customer', ['suffix'=>''])</th>
                            <th>@lang('locale.storage', ['suffix'=>''])</th>
                            <th>@lang('locale.claim', ['suffix'=>''])</th>
                            <th>@lang('locale.status')</th>
                            @if (isGroupAuthorized([1, 2]))
                            <th>@lang('locale.actions')</th>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($claims as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ date('d/m/Y H:i:s', strtotime($item->created_at)) }}</td>
                                <td>{{ $item->customer->name }}</td>
                                <td>{{ $item->storage->name }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->status }}</td>
                                @if (isGroupAuthorized([1, 2]))
                                <td>
                                    @if ($item->status == 'EN COURS')
                                    <a style="display: inline-block" class="btn btn-label-primary" title="TRAITEE" href="{{ route('claims.show', $item->id) }}"><i class="fas fa-calendar-check"></i></a>
                                    @endif
                                    <form action="{{ route('claims.destroy', $item->id) }}" method="post" style="display: inline-block">
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

    @if (isGroupAuthorized([4, 5, 6, 7, 8]))
    <form action="{{ route('claims.store') }}" method="post" id="form">
        <div class="modal fade" id="new-claim">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header bg-primary-subtle">
                        <h5 class="modal-title"><i class="fas fa-bell"></i> @lang('locale.claim', ['suffix'=>''])</h5>
                    </div>
                    <div class="modal-body">
                        @csrf
                        <div class="row">
                            <div class="col-md-8 col-sm-12 col-xs-12 mx-auto">
                                <div class="d-grid gap-3 mb-2">
                                    <div class="row">
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                            <div class="form-floating">
                                                <select class="form-select" name="name" aria-label="@lang('locale.request')" required>
                                                    <option value="">@lang('locale.select')</option>
                                                    @foreach (['RENOUVELLEMENT DUREE STOCK', 'ENTREE STOCK', 'SORTIE STOCK', 'REQUÊTE DE TRI', 'CONDITIONNEMENT GENERALE', 'CONDITIONNEMENT SPECIALE', 'LIVRAISON SANS ENCAISSEMENT', 'LIVRAISON AVEC ENCAISSEMENT'] as $item)
                                                    <option value="{{ $item }}">{{ $item }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="name">@lang('locale.request') <span class="text-danger">*</span></label>
                                            </div>                                
                                        </div>
                                        <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                            <div class="form-floating">
                                                <select class="form-select" name="storage_id" aria-label="@lang('locale.storage', ['suffix'=>''])" id="storageId" required>
                                                    <option value="">@lang('locale.select')</option>
                                                    @foreach ($storages as $item)
                                                    <option value="{{ $item->id }}" accesskey="{{ $item->available() }}">{{ $item->name." - ".$item->location." - Restant: ".$item->available()."Kg" }}</option>
                                                    @endforeach
                                                </select>
                                                <label for="storageId">@lang('locale.storage', ['suffix'=>'']) <span class="text-danger">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12">                                            
                                            <textarea name="message" style="resize: none" class="form-control" rows="10" required placeholder="@lang('locale.message')"></textarea>
                                        </div>
                                    </div>   
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> 
                        <button class="btn btn-outline-danger" data-bs-dismiss="modal" type="button">@lang('locale.close') <i class="mdi mdi-close"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
    @endif            

    @push('scripts')
    <!-- Required datatable js -->
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- buttons examples -->
    <script src="{{ asset('libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('js/pages/datatables-extension.init.js') }}"></script>
    @endpush
</x-app-layout>
