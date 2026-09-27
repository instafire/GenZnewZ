<div class="mb-3">
    <label class="control-label" for="widget-name">{{ trans('core/base::forms.name') }}</label>
    <input type="text" class="form-control" name="name" value="{{ $config['name'] }}">
</div>
<div class="mb-3">
    <label class="control-label" for="content">{{ trans('core/base::forms.content') }}</label>
    <textarea name="content" class="form-control" rows="7">{{ $config['content'] }}</textarea>
</div>
