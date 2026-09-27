<?php

namespace Botble\Setting\Forms;

use Botble\Base\Forms\FieldOptions\NumberFieldOption;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\Fields\NumberField;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Setting\Http\Requests\CacheSettingRequest;

class CacheSettingForm extends SettingForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->setUrl(route('settings.cache.update'))
            ->setSectionTitle(trans('core/setting::setting.cache.title'))
            ->setSectionDescription(trans('core/setting::setting.cache.description'))
            ->setValidatorClass(CacheSettingRequest::class)
            ->add(
                'cache_admin_menu_enable',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.cache_admin_menu'))
                    ->helperText(trans('core/setting::setting.cache.form.cache_admin_menu_helper'))
                    ->value(setting('cache_admin_menu_enable', false))
            )
            ->add(
                'cache_front_menu_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.cache_front_menu'))
                    ->helperText(trans('core/setting::setting.cache.form.cache_front_menu_helper'))
                    ->value(setting('cache_front_menu_enabled', true))
            )
            ->add(
                'cache_user_avatar_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.cache_user_avatar'))
                    ->helperText(trans('core/setting::setting.cache.form.cache_user_avatar_helper'))
                    ->value(setting('cache_user_avatar_enabled', true))
            )
            ->add(
                'shortcode_cache_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.shortcode_cache_enabled'))
                    ->helperText(trans('core/setting::setting.cache.form.shortcode_cache_enabled_helper'))
                    ->value($targetValue = setting('shortcode_cache_enabled', false))
                    ->attributes(['id' => 'shortcode-cache-settings'])
            )
            ->addOpenCollapsible('shortcode_cache_enabled', '1', $targetValue)
            ->add(
                'shortcode_cache_ttl_default',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.shortcode_cache_ttl_default'))
                    ->helperText(trans('core/setting::setting.cache.form.shortcode_cache_ttl_default_helper'))
                    ->value(setting('shortcode_cache_ttl_default', 5))
            )
            ->add(
                'shortcode_cache_ttl_cacheable',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.shortcode_cache_ttl_cacheable'))
                    ->helperText(trans('core/setting::setting.cache.form.shortcode_cache_ttl_cacheable_helper'))
                    ->value(setting('shortcode_cache_ttl_cacheable', 1800))
            )
            ->addCloseCollapsible('shortcode_cache_enabled', '1')
            ->add(
                'widget_cache_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.widget_cache_enabled'))
                    ->helperText(trans('core/setting::setting.cache.form.widget_cache_enabled_helper'))
                    ->value($targetValue = setting('widget_cache_enabled', false))
                    ->attributes(['id' => 'widget-cache-settings'])
            )
            ->addOpenCollapsible('widget_cache_enabled', '1', $targetValue)
            ->add(
                'widget_cache_ttl_default',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.widget_cache_ttl_default'))
                    ->helperText(trans('core/setting::setting.cache.form.widget_cache_ttl_default_helper'))
                    ->value(setting('widget_cache_ttl_default', 5))
            )
            ->add(
                'widget_cache_ttl_cacheable',
                NumberField::class,
                NumberFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.widget_cache_ttl_cacheable'))
                    ->helperText(trans('core/setting::setting.cache.form.widget_cache_ttl_cacheable_helper'))
                    ->value(setting('widget_cache_ttl_cacheable', 1800))
            )
            ->addCloseCollapsible('widget_cache_enabled', '1')
            ->add(
                'plugin_cache_enabled',
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('core/setting::setting.cache.form.plugin_cache_enabled'))
                    ->helperText(trans('core/setting::setting.cache.form.plugin_cache_enabled_helper'))
                    ->value(setting('plugin_cache_enabled', true))
            )
            ->when(setting('sitemap_enabled', true), function (CacheSettingForm $form): void {
                $form
                    ->add(
                        'enable_cache_site_map',
                        OnOffCheckboxField::class,
                        OnOffFieldOption::make()
                            ->label(trans('core/setting::setting.cache.form.enable_cache_site_map'))
                            ->helperText(trans('core/setting::setting.cache.form.enable_cache_site_map_helper', ['url' => url('sitemap.xml')]))
                            ->value($targetValue = setting('enable_cache_site_map', true))
                    )
                    ->addOpenCollapsible('enable_cache_site_map', '1', $targetValue)
                    ->add(
                        'cache_time_site_map',
                        NumberField::class,
                        NumberFieldOption::make()
                            ->label(trans('core/setting::setting.cache.form.cache_time_site_map'))
                            ->value(setting('cache_time_site_map', 60))
                    )
                    ->addCloseCollapsible('enable_cache_site_map', '1');
            });
    }
}
