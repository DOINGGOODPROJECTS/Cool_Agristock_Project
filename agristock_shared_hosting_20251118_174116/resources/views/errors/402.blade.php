<x-error-layout :code="402">
    <img src="{{ asset('images/errors/402.png') }}" alt="402 @lang('locale.error', ['suffix'=>''])" class="img-fluid">
    <div class="mt-5 pt-5 text-center">
        <a class="btn btn-primary waves-effect waves-light" href="{{ url()->previous() }}">@lang('locale.go_to_back')</a>
    </div>
</x-error-layout>
