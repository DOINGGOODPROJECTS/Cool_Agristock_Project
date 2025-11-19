<x-app-layout>
    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-sm-0">@lang('locale.profile')</h4>

                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">@lang('locale.dashboard')</a></li>
                        <li class="breadcrumb-item active">@lang('locale.profile')</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-xl-4">
            <div class="card overflow-hidden">
                <div class="bg-primary-subtle">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <div class="align-self-end">
                                <img src="{{ asset('images/contact.png') }}" alt="" class="img-fluid">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row align-items-end">
                        <div class="col-12">
                            <div class="avatar-md mb-3 mt-n4">
                                <img src="{{ asset('images/profile.png') }}" alt="" class="img-fluid avatar-circle bg-light p-2 border-2 border-primary">
                            </div>
                            <h5 class="fs-16 mb-1 text-truncate">{{ auth()->user()->name }}</h5>
                            <p class="text-muted mb-0 text-truncate">{{ auth()->user()->group->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="card-body border-top">
                    <div class="table-responsive">
                        <table class="table table-nowrap table-borderless mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row"><i class="mdi mdi-cellphone align-middle text-primary me-2"></i> @lang('locale.phone') :</th>
                                    <td>{{ auth()->user()->phone }}</td>
                                </tr>
                                <tr>
                                    <th scope="row"><i class="mdi mdi-email text-primary me-2"></i> @lang('locale.email') :</th>
                                    <td>{{ auth()->user()->email }}</td>
                                </tr>
                                <tr>
                                    <th scope="row"><i class="mdi mdi-account-box text-primary me-2"></i> @lang('locale.username') :</th>
                                    <td>{{ auth()->user()->username }}</td>
                                </tr>
                                <tr>
                                    <th scope="row"><i class="mdi mdi-google-translate text-primary me-2"></i> @lang('locale.language') :</th>
                                    <td>{{ auth()->user()->locale }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card-body border-top">
                    <form action="{{ route('users.update', auth()->id()) }}" method="post">
                        @csrf
                        @method('PUT')
                        <div class="d-grid gap-3">                         
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" value="{{ auth()->user()->name }}" name="name" placeholder="Ex: Paul Sylla" required/> <label for="floatingInput">@lang('locale.company') / @lang('locale.name') <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" value="{{ auth()->user()->username }}" name="username" placeholder="Ex: Pseudo" required/> <label for="floatingInput">@lang('locale.username') <span class="text-danger">*</span></label>
                                    </div>                                  
                                </div>                            
                            </div>
                            <div class="row">
                                <div class="col-md-6 col-sm-6 col-xs-12 mb-3">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" maxlength="10" value="{{ auth()->user()->phone }}" name="phone" placeholder="Ex: 0501020304" required/> <label for="floatingInput">@lang('locale.phone') <span class="text-danger">*</span></label>
                                    </div>                                  
                                </div>
                                <div class="col-md-6 col-sm-6 col-xs-12">
                                    <div class="form-floating">
                                        <input type="email" class="form-control" value="{{ auth()->user()->email }}" name="email" placeholder="Ex: contact@agristock.com" required/> <label for="floatingInput">@lang('locale.email') <span class="text-danger">*</span></label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 mt-4"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Activity</h4>
                </div>
                <div class="card-body">
                    <div id="column_rotated_labels" data-colors='["--bs-primary"]' class="apex-charts" dir="ltr"></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">My Projects</h4>
                </div>
                <div class="card-body">
                    <table id="datatable" class="table table-hover table-bordered table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Projects</th>
                                <th>Start date</th>
                                <th>Deadline</th>
                                <th>Budgets</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>01</td>
                                <td>Logo Branding</td>
                                <td>2011/04/25</td>
                                <td>15</td>
                                <td>$12</td>
                            </tr>
                            <tr>
                                <td>02</td>
                                <td>Dashboard</td>
                                <td>2011/07/25</td>
                                <td>10</td>
                                <td>$23</td>
                            </tr>
                            <tr>
                                <td>03</td>
                                <td>Pages</td>
                                <td>2009/01/12</td>
                                <td>20</td>
                                <td>$36</td>
                            </tr>
                            <tr>
                                <td>04</td>
                                <td>Apps</td>
                                <td>2012/03/29</td>
                                <td>19</td>
                                <td>$42</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- end row -->

    @push('scripts')
    <!-- apexcharts -->
    <script src="{{ asset('libs/apexcharts/apexcharts.min.js') }}"></script>

    <!-- Required datatable js -->
    <script src="{{ asset('libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>

    <!-- Responsive examples -->
    <script src="{{ asset('libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js') }}"></script>

    <!-- contact init js -->
    <script src="{{ asset('js/pages/profile.init.js') }}"></script>

    <!-- Datatable init js -->
    <script src="{{ asset('js/pages/datatables-base.init.js') }}"></script>
    @endpush
</x-app-layout>
