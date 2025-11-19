<div class="custom-setting bg-info pe-0 d-flex flex-column rounded-start">
    <button type="button" class="btn btn-wide border-0 text-white fs-20 avatar-sm rounded-end-0" id="light-dark-mode">
        <i class="mdi mdi-brightness-7 align-middle"></i>
        <i class="mdi mdi-white-balance-sunny align-middle"></i>
    </button>
    <button type="button" class="btn btn-wide border-0 text-white fs-20 avatar-sm" data-toggle="fullscreen">
        <i class="mdi mdi-arrow-expand-all align-middle"></i>
    </button>
    <a role="button" class="btn btn-wide border-0 text-white fs-16 avatar-sm" title="@lang('locale.switch_locale', ['param'=>app()->getLocale() == 'en' ? __('locale.french') : __('locale.english')])" href="{{ route('locale.update', app()->getLocale() == 'en' ? 'fr' : 'en') }}">
        <i class="mdi mdi-translate align-middle"></i>
    </a>
</div>