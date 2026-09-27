@if (File::exists(public_path('ads.txt')))
    <x-core::button
        type="button"
        color="danger"
        size="md"
        name="google_adsense_ads_delete_txt"
        onclick="if (confirm('{{ trans('plugins/ads::ads.settings.confirm_delete_ads_txt') }}')) { event.preventDefault(); document.getElementById('delete-ads-txt').submit(); }"
    >
        <x-core::icon name="ti ti-trash" /> {{ trans('plugins/ads::ads.settings.delete_ads_txt') }}
    </x-core::button>

    <small class="form-hint mt-2">
        {!! BaseHelper::clean(trans('plugins/ads::ads.settings.view_ads_txt', ['url' => Html::link(url('ads.txt'), attributes: ['target' => '_blank'])])) !!}
    </small>
@endif
