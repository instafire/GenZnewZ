<?php

namespace Botble\Setting\Http\Requests;

use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;

class CacheSettingRequest extends Request
{
    public function rules(): array
    {
        $onOffRule = new OnOffRule();

        return [
            'cache_admin_menu_enable' => [$onOffRule],
            'enable_cache_site_map' => [$onOffRule],
            'cache_front_menu_enabled' => [$onOffRule],
            'cache_user_avatar_enabled' => [$onOffRule],
            'shortcode_cache_enabled' => [$onOffRule],
            'widget_cache_enabled' => [$onOffRule],
            'plugin_cache_enabled' => [$onOffRule],
            'cache_time_site_map' => ['nullable', 'required_if:enable_cache_site_map,1', 'integer', 'min:1'],
            'shortcode_cache_ttl_default' => ['nullable', 'required_if:shortcode_cache_enabled,1', 'integer', 'min:1'],
            'shortcode_cache_ttl_cacheable' => ['nullable', 'required_if:shortcode_cache_enabled,1', 'integer', 'min:1'],
            'widget_cache_ttl_default' => ['nullable', 'required_if:widget_cache_enabled,1', 'integer', 'min:1'],
            'widget_cache_ttl_cacheable' => ['nullable', 'required_if:widget_cache_enabled,1', 'integer', 'min:1'],
        ];
    }
}
