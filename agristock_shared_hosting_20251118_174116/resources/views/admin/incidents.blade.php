<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.incident', ['suffix'=>'s'])</h4>

                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-incident"><i class="fas fa-exclamation-triangle"></i> @lang('locale.add')</button>
                </div>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-footer-callback" class="table table-sm table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>@lang('locale.status')</th>
                            <th>@lang('locale.type', ['suffix'=>''])</th>
                            <th>@lang('locale.description')</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($incidents as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->status }}</td>
                                <td>{{ $item->type }}</td>
                                <td>{{ $item->description }}</td>
                                <td>
                                    @if ($item->status == 'EN COURS')
                                    <a style="display: inline-block" class="btn btn-label-primary" href="{{ route('incidents.status', ['status'=>'RESOLU', 'id'=>$item->id]) }}"><i class="fas fa-check"></i></a>
                                    <a style="display: inline-block" class="btn btn-label-danger" href="{{ route('incidents.status', ['status'=>'NON RESOLU', 'id'=>$item->id]) }}"><i class="fas fa-ban"></i></a>
                                    @endif
                                    <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-incident{{ $item->id }}"><i class="fas fa-edit"></i></a>
                                    <x-edit-incident :incident="$item"></x-edit-incident>
                                    <form action="{{ route('incidents.destroy', $item->id) }}" method="post" style="display: inline-block">
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

    <div class="modal fade" id="new-incident">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.incident', ['suffix'=>''])</h5>
                </div>
                <form action="{{ route('incidents.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">
                            <div class="form-floating">
                                <select class="form-select" name="type" aria-label="@lang('locale.type', ['suffix'=>''])" required>
                                    @foreach (['INCIDENT SPECIFIQUE', 'INCIDENT GENERAL'] as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                                <label for="type">@lang('locale.type', ['suffix'=>'']) <span class="text-danger">*</span></label>
                            </div> 
                            <div>
                                <label for="message" class="form-label">@lang('locale.message') <span class="text-danger">*</span></label> 
                                <textarea class="form-control" name="description" id="description" rows="3" style="resize: none"></textarea>
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
