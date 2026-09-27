<?php

namespace Botble\Base\Forms\FieldOptions;

class AutocompleteFieldOption extends SelectFieldOption
{
    protected bool $searchable = true;

    protected string $ajaxUrl;

    protected int $minimumInputLength = 1;

    protected string $requestType = 'GET';

    protected string $placeholder;

    public function ajaxUrl(string $url): static
    {
        $this->ajaxUrl = $url;
        $this->addAttribute('data-url', $url);

        return $this;
    }

    public function minimumInputLength(int $length): static
    {
        $this->minimumInputLength = $length;
        $this->addAttribute('data-minimum-input', $length);

        return $this;
    }

    public function requestType(string $type): static
    {
        $this->requestType = strtoupper($type);
        $this->addAttribute('data-type', $this->requestType);

        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        $this->addAttribute('data-placeholder', $placeholder);

        return $this;
    }

    public function toArray(): array
    {
        $data = parent::toArray();

        // Ensure the select-autocomplete class is always present
        $existingClass = $data['attr']['class'] ?? '';
        if (! str_contains($existingClass, 'select-autocomplete')) {
            $data['attr']['class'] = trim($existingClass . ' select-autocomplete');
        }

        // Add data-selected attribute for pre-selected values (similar to select-search-ajax)
        if (isset($data['selected']) && $data['selected'] && isset($data['choices']) && ! empty($data['choices'])) {
            $selectedData = [];
            if (is_array($data['selected'])) {
                foreach ($data['selected'] as $selectedId) {
                    if (isset($data['choices'][$selectedId])) {
                        $selectedData[$selectedId] = $data['choices'][$selectedId];
                    }
                }
            } else {
                if (isset($data['choices'][$data['selected']])) {
                    $selectedData[$data['selected']] = $data['choices'][$data['selected']];
                }
            }

            if (! empty($selectedData)) {
                $data['attr']['data-selected'] = json_encode($selectedData);
            }
        }

        return $data;
    }
}
