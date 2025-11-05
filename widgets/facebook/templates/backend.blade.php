<div class="mb-3">
    <label class="control-label" for="widget-name">{{ __('Name') }}</label>
    <input type="text" class="form-control" name="name" value="{{ $config['name'] }}">
</div>

<div class="mb-3">
    <label class="control-label" for="widget-facebook-name">{{ __('Facebook FanPage Name') }}</label>
    <input type="text" class="form-control" name="facebook_name" value="{{ $config['facebook_name'] }}">
</div>

<div class="mb-3">
    <label class="control-label" for="widget-facebook-id">{{ __('Facebook URL') }}</label>
    <input type="text" class="form-control" name="facebook_url" value="{{ $config['facebook_url'] }}">
</div>

<div class="mb-3">
    <label class="control-label" for="widget-facebook-id">{{ __('Facebook Tabs') }}</label>
    <input type="text" name="facebook_tabs" class="form-control" value="{{ $config['facebook_tabs'] }}" placeholder="{{ __('e.g., timeline, messages, events') }}">
</div>
