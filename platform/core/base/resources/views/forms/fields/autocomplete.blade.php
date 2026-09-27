<x-core::form.field
    :showLabel="$showLabel"
    :showField="$showField"
    :options="$options"
    :name="$name"
    :prepend="$prepend ?? null"
    :append="$append ?? null"
    :showError="$showError"
    :nameKey="$nameKey"
>
    <x-slot:label>
        @if ($showLabel && $options['label'] !== false && $options['label_show'])
            {!! Form::customLabel($name, $options['label'], $options['label_attr']) !!}
        @endif
    </x-slot:label>

    @php
        if ($options['choices'] instanceof \Illuminate\Contracts\Support\Arrayable) {
            $options['choices'] = $options['choices']->toArray();
        }

        // Merge the select-autocomplete class with existing classes
        $options['attr']['class'] = trim(($options['attr']['class'] ?? '') . ' select-autocomplete');
    @endphp

    {!! Form::customSelect(
        $name,
        ($options['empty_value'] ? ['' => $options['empty_value']] : []) + $options['choices'],
        $options['selected'] !== null ? $options['selected'] : $options['default_value'],
        $options['attr'],
        Arr::get($options, 'optionAttrs', []),
        Arr::get($options, 'optgroupsAttributes', []),
    ) !!}
</x-core::form.field>
