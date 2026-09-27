<?php

namespace Botble\Base\Forms\FieldOptions;

class MultiChecklistFieldOption extends SelectFieldOption
{
    protected bool $inline = false;

    public function placeholder(string $placeholder): static
    {
        $this->addAttribute('placeholder', $placeholder);

        return $this;
    }

    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        if (isset($this->emptyValue)) {
            $data['empty_value'] = $this->getEmptyValue();
        }

        if (isset($this->inline)) {
            $data['inline'] = $this->inline;
        }

        return $data;
    }
}
