<x-app-layout>
    @push('links')
    <link href="{{ asset('libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
    @endpush

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.user', ['suffix'=>'s'])</h4>

                <div class="page-title-right">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#new-user"><i class="fas fa-user-plus"></i> @lang('locale.add')</button>
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
                            <th>@lang('locale.name')</th>
                            <th>@lang('locale.phone')</th>
                            <th>@lang('locale.email')</th>
                            <th>@lang('locale.group', ['suffix'=>''])</th>
                            <th>@lang('locale.actions')</th>
                        </thead>
                        <tbody>
                            @foreach ($users as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->phone }}</td>
                                <td>{{ $item->email }}</td>
                                <td>{{ $item->group->name }}</td>
                                <td>
                                    <a style="display: inline-block" class="btn btn-label-info" data-bs-toggle="modal" data-bs-target="#edit-user{{ $item->id }}"><i class="fas fa-edit"></i></a>
                                    <x-edit-user :user="$item" :groups="$groups" :entity="__('locale.user', ['suffix'=>''])"></x-edit-user>
                                    <form action="{{ route('users.destroy', $item->id) }}" method="post" style="display: inline-block">
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

    <div class="modal fade" id="new-user">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary-subtle">
                    <h5 class="modal-title">@lang('locale.user', ['suffix'=>''])</h5>
                </div>
                <form action="{{ route('users.store') }}" method="post">
                    <div class="modal-body">
                        @csrf
                        <div class="d-grid gap-3">    
                            <div class="row">
                                <div class="col-12 text-center">@lang('locale.default_pwd'): <code>CoolAgriStock@225</code></div>
                            </div>                          
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="name" name="name" placeholder="Ex: Paul Sylla" required/> <label for="floatingInput">@lang('locale.name') <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Ex: Pseudo" required/> <label for="floatingInput">@lang('locale.username') <span class="text-danger">*</span></label>
                                    </div>                                  
                                </div>                                
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" maxlength="10" id="phone" name="phone" placeholder="Ex: 0501020304" required/> <label for="floatingInput">@lang('locale.phone') <span class="text-danger">*</span></label>
                                    </div>                                  
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Ex: contact@agristock.com" required/> <label for="floatingInput">@lang('locale.email') <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-floating">
                                <select class="form-select" id="groupId" name="group_id" aria-label="@lang('locale.group', ['suffix'=>app()->getLocale() == 'en' ? 'y' : ''])" required>
                                    @foreach ($groups as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                <label for="groupId">@lang('locale.group', ['suffix'=>'']) <span class="text-danger">*</span></label>
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
