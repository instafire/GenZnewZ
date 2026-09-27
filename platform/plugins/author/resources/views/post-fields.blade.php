<div class="form-group">
    <label for="author_id">{{ __('Author') }}</label>
    {!! Form::customSelect('author_id', ['' => __('Select an author...')] + $authorsArray, $authorId, [
        'class' => 'form-control',
    ]) !!}
</div>
