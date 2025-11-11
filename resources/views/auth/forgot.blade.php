<x-auth-layout>
    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        @if (session('status'))
            <div class="alert alert-success text-center small mb-3">
                {{ session('status') }}
            </div>
        @endif

        <!-- Email Address -->
        <p class="text-muted text-center">
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </p>
        
        <div class="d-grid gap-3">
            <div class="form-floating">
                <input type="email" class="form-control" id="email" name="email" placeholder="{{ __('locale.email') }}" required/> <label for="floatingInput">@lang('locale.email')</label>
            </div>
        </div>
        <div class="pt-3 text-center">
            <x-btn class="btn btn-primary w-xl waves-effect waves-light">@lang('locale.send_reset_link')</x-btn>
        </div>
        <div class="mt-3 text-center">
            <p class="mb-0">@lang('locale.have_account') ? <a href="{{ route('login') }}" class="fw-medium text-primary"> @lang('locale.sign_in')</a> </p>
        </div>
    </form>
</x-auth-layout>
