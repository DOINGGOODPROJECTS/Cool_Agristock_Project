<x-guest-layout>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="d-grid gap-3">
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="{{ __('locale.email') }}" required/> <label for="floatingInput">@lang('locale.email')</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('locale.password') }}" required/> <label for="floatingInput">@lang('locale.password')</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ __('locale.password_confirmation') }}" required/> <label for="floatingInput">@lang('locale.password_confirmation')</label>
            </div>
        </div>

        <div class="pt-3 text-center">
            <x-btn class="btn btn-primary w-xl waves-effect waves-light">@lang('locale.reset_pwd')</x-btn>
        </div>
    </form>
</x-guest-layout>
