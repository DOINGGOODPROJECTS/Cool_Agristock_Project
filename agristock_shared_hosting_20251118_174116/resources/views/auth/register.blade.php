<x-auth-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <p class="text-muted text-center"><x-input-error :messages="$errors->get('auth')" class="mt-2" /></p>
                
        <div class="d-grid gap-3">
            <div class="form-floating">
                <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('locale.name') }}" required/> <label for="floatingInput">@lang('locale.name')</label>
            </div>
            <div class="form-floating">
                <input type="tel" class="form-control" maxlength="10" id="phone" name="phone" placeholder="{{ __('locale.phone') }}" required/> <label for="floatingInput">@lang('locale.phone')</label>
            </div>
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="{{ __('locale.email') }}" required/> <label for="floatingInput">@lang('locale.email')</label>
            </div>
            <div class="form-floating">
                <input type="text" class="form-control" id="username" name="username" placeholder="{{ __('locale.username') }}" required/> <label for="floatingInput">@lang('locale.username')</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('locale.password') }}" required/> <label for="floatingInput">@lang('locale.password')</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" placeholder="{{ __('locale.password_confirmation') }}" required/> <label for="floatingInput">@lang('locale.password_confirmation')</label>
            </div>
            <div class="form-floating">
                <select class="form-select" name="group_id" aria-label="@lang('locale.company')" required>
                    @foreach ($groups as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
                <label for="cityId">@lang('locale.company')</label>
            </div> 

            <div class="form-check float-sm-start">
                <input type="checkbox" class="form-check-input" id="customControlInline" name="remember" required>
                <label class="form-check-label" for="customControlInline">@lang('locale.agree_privacy_politics')</label>
            </div>
        </div>

        <div class="pt-3 text-center">
            <x-btn class="btn btn-primary w-xl waves-effect waves-light">@lang('locale.sign_up')</x-btn>
        </div>

        <div class="mt-3 text-center">
            <p class="mb-0">@lang('locale.have_account') ? <a href="{{ route('login') }}" class="fw-medium text-primary"> @lang('locale.sign_in')</a> </p>
        </div>
    </form>
</x-auth-layout>
