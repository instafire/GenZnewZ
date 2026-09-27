<?php

namespace Botble\Base\Forms\Fields;

use Botble\Base\Forms\FieldOptions\TreeCategoryFieldOption;
use Botble\Base\Forms\FormField;

class TreeCategoryField extends FormField
{
    public function getFieldOption(): string
    {
        return TreeCategoryFieldOption::class;
    }

    protected function getTemplate(): string
    {
        return 'core/base::forms.fields.tree-categories';
    }
}
