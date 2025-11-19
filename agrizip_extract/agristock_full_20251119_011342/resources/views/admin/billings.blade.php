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
                <h4 class="mb-sm-0">@lang('locale.billing', ['suffix'=>'s'])</h4>
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
                            <th>@lang('locale.customer', ['suffix'=>''])</th>
                            <th>@lang('locale.stock', ['suffix'=>''])</th>
                            <th>@lang('locale.amount')</th>
                            <th>@lang('locale.remaining_amount')</th>
                            @if (isGroupAuthorized([1, 2]))
                            <th>@lang('locale.actions')</th>
                            @endif
                        </thead>
                        <tbody>
                            @foreach ($billings as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->customer->name }}</td>
                                <td>{{ $item->stock->ref }}</td>
                                <td>{{ moneyFormat($item->amount) }}</td>
                                <td>{{ moneyFormat($item->amount-$item->payments->sum('amount')) }}</td>
                                @if (isGroupAuthorized([1, 2]))
                                <td>
                                    @if ($item->payments->sum('amount') < $item->amount)
                                    <a style="display: inline-block" class="btn btn-label-primary" data-bs-toggle="modal" data-bs-target="#make-payment{{ $item->id }}" title="@lang('locale.make_payment')"><i class="fab fa-amazon-pay"></i></a>
                                    <x-make-payment :billing="$item"></x-make-payment>                       
                                    @elseif($item->stock->qty == 0)
                                    <div class="row icon-demo-content" style="display: inline-block" title="@lang('locale.paid') & @lang('locale.released')">
                                        <div class="col-2">
                                            <i class="fas fa-check-double text-primary"></i>
                                        </div>
                                    </div>
                                    @else
                                    <div class="row icon-demo-content" style="display: inline-block" title="@lang('locale.paid')">
                                        <div class="col-2">
                                            <i class="fas fa-check text-primary"></i>
                                        </div>
                                    </div>
                                    @endif
                                    
                                    <a style="display: inline-block" class="btn btn-label-info" href="{{ route('billings.show', $item->id) }}" title="@lang('locale.details')"><i class="fa fa-eye"></i></a>
                                    <form action="{{ route('billings.destroy', $item->id) }}" method="post" style="display: inline-block">
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
