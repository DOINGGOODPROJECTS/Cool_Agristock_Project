<div class="sidebar-left">
    <div data-simplebar class="h-100 d-flex flex-column">
        <!--- Sidebar-menu -->
        <div id="sidebar-menu">
            <!-- Left Menu Start -->
            <ul class="left-menu list-unstyled" id="side-menu">
                <li>
                    <a href="{{ route('dashboard') }}" class="">
                        <i class="fas fa-desktop"></i> <span>@lang('locale.dashboard')</span>
                    </a>
                </li>
                
                <li class="menu-title">@lang('locale.management')</li>
                
                @if (isGroupAuthorized([1, 2]))
                <li><a href="{{ route('customers') }}"><i class="fa fa-users"></i> <span>@lang('locale.customer', ['suffix'=>'s'])</span></a></li>
                <li><a href="{{ route('categories.index') }}"><i class="fa fa-dumpster"></i> <span>@lang('locale.category', ['suffix'=>app()->getLocale() == 'en' ? 'ies' : 's'])</span></a></li>
                <li><a href="{{ route('incidents.index') }}"><i class="fas fa-exclamation-triangle"></i> <span>@lang('locale.incident', ['suffix'=>'s'])</span></a></li>

                <li>
                    <a href="javascript: void(0);" class="has-arrow">
                        <i class="fa fa-folder"></i> <span>@lang('locale.user', ['suffix'=>'s'])</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('groups') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i>@lang('locale.group', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('users.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.user', ['suffix'=>'s'])</a></li>
                    </ul>
                </li>
                @endif    

                @if (isGroupAuthorized([1, 2, 5, 6, 7, 8]))
                <li>
                    <a href="javascript: void(0);" class="has-arrow">
                        <i class="fa fa-folder"></i> <span>@lang('locale.stock', ['suffix'=>'s'])</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        @if (isGroupAuthorized([1, 2]))
                        <li><a href="{{ route('storages.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.storage', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('temperatures.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i>@lang('locale.temperature', ['suffix'=>'s'])</a></li>
                        @endif

                        <li><a href="{{ route('stocks.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.stock', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('releases.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.release', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('rottens.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.rotten', ['suffix'=>'s'])</a></li>
                    
                        @if (isGroupAuthorized([1, 2]))
                        <li><a href="{{ route('capacities.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.capacity', ['suffix'=>app()->getLocale() == 'en' ? 'ies' : 's'])</a></li>
                        <li><a href="{{ route('products.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.product', ['suffix'=>'s'])</a></li>
                        @endif
                    </ul>
                </li>
                @endif  
                
                @if (isGroupAuthorized([1, 3, 4, 5, 6, 7, 8]))
                <li>
                    <a href="javascript: void(0);" class="has-arrow">
                        <i class="fa fa-folder"></i> <span>@lang('locale.accounting', ['suffix'=>''])</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('billings.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i>@lang('locale.billing', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('payments.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.payment', ['suffix'=>'s'])</a></li>
                        <li><a href="{{ route('tariffs.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> @lang('locale.tariff', ['suffix'=>'s'])</a></li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('claims.index') }}">
                        <i class="fas fa-bell"></i> <span>@lang('locale.claim', ['suffix'=>'s'])</span>
                    </a>
                </li>
                @endif  

                {{-- ── Sync Layer ─────────────────────────────────────── --}}
                <li class="menu-title">Sync</li>

                <li>
                    <a href="{{ route('inventory-ops.index') }}">
                        <i class="fas fa-sync-alt"></i> <span>Inventory Ops</span>
                    </a>
                </li>

                @if (isGroupAuthorized([1, 2, 3, 4]))
                <li>
                    <a href="javascript: void(0);" class="has-arrow">
                        <i class="fa fa-folder"></i> <span>Sync Admin</span>
                    </a>
                    <ul class="sub-menu" aria-expanded="false">
                        <li><a href="{{ route('sync-sessions.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> Sessions</a></li>
                        <li><a href="{{ route('sync-audit-log.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> Audit Log</a></li>
                        @if (isGroupAuthorized([1]))
                        <li><a href="{{ route('member-phones.index') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> Member Phones</a></li>
                        @endif
                        <li><a href="{{ route('sync-protocol') }}"><i class="mdi mdi-checkbox-blank-circle align-middle"></i> Protocol Spec</a></li>
                    </ul>
                </li>
                @endif
                {{-- ─────────────────────────────────────────────────── --}}

                <li><a href="{{ route('logout') }}" class="text-danger"><i class="fa fa-power-off"></i> <span>@lang('locale.logout')</span></a></li>
            </ul>
        </div>
        <div class="sidebar-export-actions mt-auto px-3 pb-3 pt-3 border-top">
            @if (isGroupAuthorized([1, 2, 3, 4]))
            <div class="d-grid gap-2">
                <a href="{{ route('exports.individual') }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2">
                    <i class="fa fa-file-alt"></i>
                    <span>@lang('locale.export_individual')</span>
                </a>
                <a href="{{ route('exports.all') }}" class="btn btn-primary btn-sm d-flex align-items-center justify-content-center gap-2" download>
                    <i class="fa fa-file-export"></i>
                    <span>@lang('locale.export_all')</span>
                </a>
            </div>
            @endif
        </div>
        <!-- Sidebar -->
    </div>
</div>
