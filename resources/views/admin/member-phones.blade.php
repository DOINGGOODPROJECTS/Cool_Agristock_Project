<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">Member Phones</h4>
                <div class="page-title-right d-flex align-items-center gap-2">
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#new-phone">
                        <i class="fas fa-plus"></i> Register Phone
                    </button>
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('locale.dashboard')</a></li>
                        <li class="breadcrumb-item active">Member Phones</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table id="datatable-buttons" class="table table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <th>#</th>
                            <th>Member</th>
                            <th>Group</th>
                            <th>Phone</th>
                            <th>Verified</th>
                            <th>Registered At</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($phones as $phone)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $phone->user->name ?? '—' }}</td>
                                <td>{{ $phone->user->group->name ?? '—' }}</td>
                                <td>{{ $phone->phone }}</td>
                                <td>
                                    @if ($phone->verified_at)
                                    <span class="badge bg-success"><i class="fas fa-check"></i> {{ date('d/m/Y', strtotime($phone->verified_at)) }}</span>
                                    @else
                                    <span class="badge bg-warning">Unverified</span>
                                    @endif
                                </td>
                                <td>{{ date('d/m/Y H:i', strtotime($phone->created_at)) }}</td>
                                <td>
                                    @if (!$phone->verified_at)
                                    <form action="{{ route('member-phones.verify', $phone->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        <button class="btn btn-sm btn-label-success" title="Mark Verified"><i class="fas fa-check"></i></button>
                                    </form>
                                    @endif
                                    <form action="{{ route('member-phones.destroy', $phone->id) }}" method="POST" style="display:inline-block">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-label-danger" onclick="return confirm('Remove this phone?')"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Register Phone Modal --}}
    <div class="modal fade" id="new-phone" tabindex="-1">
        <div class="modal-dialog">
            <form action="{{ route('member-phones.store') }}" method="POST">
                @csrf
                <div class="modal-content">
                    <div class="modal-header bg-primary-subtle">
                        <h5 class="modal-title">Register Member Phone</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Member</label>
                            <select class="form-select" name="user_id" required>
                                <option value="">— Select member —</option>
                                @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->group->name ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="text" class="form-control" name="phone" placeholder="e.g. +2250700000000" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Register <i class="fas fa-check"></i></button>
                        <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('libs/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/pdfmake.min.js') }}"></script>
    <script src="{{ asset('libs/pdfmake/build/vfs_fonts.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('js/pages/datatables-extension.init.js') }}"></script>
    @endpush
</x-app-layout>
