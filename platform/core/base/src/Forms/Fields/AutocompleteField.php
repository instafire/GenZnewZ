<?php

namespace Botble\Base\Forms\Fields;

class AutocompleteField extends SelectField
{
    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.autocomplete';
    }

    public function getDefaults(): array
    {
        return [
            'choices' => [],
            'option_attributes' => [],
            'empty_value' => null,
            'selected' => null,
            'attr' => [
                'class' => 'select-autocomplete',
                'data-minimum-input' => 1,
            ],
        ];
    }
}
