<x-auth-layout>
    <form method="POST" action="{{ route('auth') }}">
        @csrf

        <p class="text-muted text-center"><x-input-error :messages="$errors->get('auth')" class="mt-2" /></p>
        <!-- Email Address -->
        <div class="input-group auth-form-group-custom mb-3">
            <span class="input-group-text bg-info bg-opacity-10 fs-16 " for="email"><i class="mdi mdi-account-outline auti-custom-input-icon"></i></span>
            <x-text-input id="email" type="text" name="email" placeholder="{{ __('locale.email').' | '.__('locale.username') }}" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <!-- Password -->
        <div class="input-group auth-form-group-custom mb-3">
            <span class="input-group-text bg-info bg-opacity-10 fs-16 btn-hide-show-pwd" for="password"><i class="mdi mdi-eye-off auti-custom-input-icon"></i></span>
            <x-text-input id="password" type="password" name="password" placeholder="{{ __('locale.password') }}" required autocomplete="current-password" />
        </div>

        <div class="mb-sm-5">
            <div class="form-check float-sm-start">
                <input type="checkbox" class="form-check-input" id="customControlInline" name="remember">
                <label class="form-check-label" for="customControlInline">@lang('locale.remember_me')</label>
            </div>
            @if (Route::has('password.request'))
            <div class="float-sm-end">
                <a href="{{ route('password.request') }}" class="text-muted"><i class="mdi mdi-lock me-1"></i> @lang('locale.forgot_pwd')?</a>
            </div>
            @endif
        </div>

        <div class="pt-3 text-center">
            <x-btn class="btn btn-info w-xl waves-effect waves-light">@lang('locale.sign_in')</x-btn>
        </div>

        <!--<div class="mt-3 text-center">-->
        <!--    <p class="mb-0">@lang('locale.dont_have_account') ? <a href="{{ route('register') }}" class="fw-medium text-info"> @lang('locale.sign_up')</a> </p>-->
        <!--</div>-->
    </form>

    @push('scripts')
    <script>
        $(document).ready(function () {
            $(".btn-hide-show-pwd").on('click', function (event) {
                let pwdinput = $(this).parent('div.input-group').children('input');
                if (pwdinput.attr("type") == "text") {
                    pwdinput.attr('type', 'password');
                    $(this).children('i').addClass("mdi-eye-off");
                    $(this).children('i').removeClass("mdi-eye");
                } else {
                    pwdinput.attr('type', 'text');
                    $(this).children('i').addClass("mdi-eye");
                    $(this).children('i').removeClass("mdi-eye-off");
                }
            });
        });
    </script>
    @endpush
</x-auth-layout>
