<x-app-layout>
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <h4 class="mb-1">Data Export</h4>
            <p class="text-muted mb-0">Browse data, pick rows, and export to CSV, PDF, or Excel.</p>
        </div>
        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ route('exports.all') }}" download>
                <i class="fa fa-download me-1"></i> @lang('locale.export_all')
            </a>
        </div>
    </div>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($tables->isEmpty())
        <div class="alert alert-warning mb-0">
            No tables available for export yet.
        </div>
    @else
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('exports.individual') }}" class="row gy-3 align-items-end">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label class="form-label fw-semibold">Dataset</label>
                        <select class="form-select" name="table" onchange="this.form.submit()">
                            @foreach ($tables as $table)
                                <option value="{{ $table }}" {{ $table === $activeTable ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $table)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4 ms-auto text-md-end">
                        <div class="text-muted small">Showing first 200 rows from {{ $activeTable }}</div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <form id="exportForm" method="POST" action="{{ route('exports.individual.export') }}">
                    @csrf
                    <input type="hidden" name="table" value="{{ $activeTable }}">
                    <input type="hidden" name="format" id="exportFormat" value="csv">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll" />
                            <label class="form-check-label" for="selectAll">Select all rows</label>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-secondary btn-sm export-btn" data-format="csv">
                                <i class="fa fa-file-csv me-1"></i> CSV
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm export-btn" data-format="pdf">
                                <i class="fa fa-file-pdf me-1"></i> PDF
                            </button>
                            <button type="button" class="btn btn-primary btn-sm export-btn" data-format="excel">
                                <i class="fa fa-file-excel me-1"></i> Excel
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 45px;"></th>
                                    @foreach ($columns as $column)
                                        <th class="text-capitalize">{{ $columnLabels[$loop->index] ?? $column }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $index => $row)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input select-row" name="rows[]" value="{{ $hasId ? $row->id : $index }}">
                                        </td>
                                        @foreach ($columns as $column)
                                            <td>{{ $row->{$column} ?? '' }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ count($columns) + 1 }}" class="text-center text-muted py-4">
                                            No data available in {{ $activeTable }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const selectAll = document.getElementById('selectAll');
                const rowCheckboxes = document.querySelectorAll('.select-row');
                const exportButtons = document.querySelectorAll('.export-btn');
                const formatField = document.getElementById('exportFormat');
                const exportForm = document.getElementById('exportForm');

                if (selectAll) {
                    selectAll.addEventListener('change', (event) => {
                        rowCheckboxes.forEach((checkbox) => {
                            checkbox.checked = event.target.checked;
                        });
                    });
                }

                exportButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        formatField.value = button.dataset.format;
                        exportForm.submit();
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
