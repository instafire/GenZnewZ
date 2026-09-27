<?php

namespace Botble\Base\Forms\FieldOptions;

class TreeCategoryFieldOption extends SelectFieldOption
{
    protected int $switchToDropdownThreshold = 50;

    /**
     * Set the maximum number of items before switching from tree view to dropdown view.
     * When the number of items exceeds this value, the display will change from a tree structure to a dropdown select.
     */
    public function switchToDropdownThreshold(int $threshold): static
    {
        $this->switchToDropdownThreshold = $threshold;

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        $data['switch_to_dropdown_threshold'] = $this->switchToDropdownThreshold;

        return $data;
    }
}
