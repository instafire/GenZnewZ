<?php

namespace Botble\Blog\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Base\Rules\OnOffRule;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CategoryRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:400'],
            'status' => [Rule::in(BaseStatusEnum::values())],
            'is_default' => [new OnOffRule()],
            'is_featured' => [new OnOffRule()],
            'parent_id' => [
                'nullable',
                Rule::when($this->input('parent_id'), function () {
                    return Rule::exists('categories', 'id');
                }),
            ],
            'order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('core/base::forms.name'),
            'description' => trans('core/base::forms.description'),
            'status' => trans('core/base::tables.status'),
            'is_default' => trans('core/base::forms.is_default'),
            'is_featured' => trans('core/base::forms.is_featured'),
            'parent_id' => trans('core/base::forms.parent'),
            'order' => trans('core/base::forms.order'),
        ];
    }
}
