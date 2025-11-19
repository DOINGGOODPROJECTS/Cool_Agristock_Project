<div class="modal fade" id="edit-user{{ $user->id }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-info-subtle">
                <h5 class="modal-title">{{ $entity }}</h5>
            </div>
            <form action="{{ route('users.update', $user->id) }}" method="post">
                <div class="modal-body">
                    @csrf
                    @method('PUT')
                    <div class="d-grid gap-3">                         
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="{{ $user->name }}" name="name" placeholder="Ex: Paul Sylla" required/> <label for="floatingInput">{{ $entity == 'Customer' ? __('locale.company')." / ".__('locale.name') : __('locale.name') }} <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="text" class="form-control" value="{{ $user->username }}" name="username" placeholder="Ex: Pseudo"/> <label for="floatingInput">@lang('locale.username')</label>
                                </div>                                  
                            </div>                            
                        </div>
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" maxlength="10" value="{{ $user->phone }}" name="phone" placeholder="Ex: 0501020304" required/> <label for="floatingInput">@lang('locale.phone') <span class="text-danger">*</span></label>
                                </div>                                  
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-12">
                                <div class="form-floating">
                                    <input type="email" class="form-control" value="{{ $user->email }}" name="email" placeholder="Ex: contact@agristock.com"/> <label for="floatingInput">@lang('locale.email')</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-floating">
                            <select class="form-select" id="groupId" name="group_id" aria-label="@lang('locale.group', ['suffix'=>''])" required>
                                @foreach ($groups as $item)
                                <option value="{{ $item->id }}" {{ $user->group_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                @endforeach
                            </select>
                            <label for="groupId">{{ $entity == 'Customer' ? __('locale.category', ['suffix'=>app()->getLocale() == 'en' ? 'y' : '']) : __('locale.group', ['suffix'=>'']) }} <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">@lang('locale.submit') <i class="fas fa-check"></i></button> <button class="btn btn-outline-danger" data-bs-dismiss="modal">@lang('locale.close') <i class="mdi mdi-close"></i></button></div>
            </form>
        </div>
    </div>
</div>