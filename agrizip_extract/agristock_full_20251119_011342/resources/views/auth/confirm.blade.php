<x-auth-layout>
    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf
        <p class="text-muted text-center"><x-input-error :messages="$errors->get('password')" class="mt-2" /></p>

        <div class="d-grid gap-3">
            <div class="form-floating">
                <input type="password" class="form-control" id="password" name="password" placeholder="{{ __('locale.password') }}" required/> <label for="floatingInput">@lang('locale.password')</label>
            </div>
        </div>

        <div class="pt-3 text-center">
            <x-btn class="btn btn-primary w-xl waves-effect waves-light">@lang('locale.confirm')</x-btn>
        </div>
    </form>
</x-auth-layout>
