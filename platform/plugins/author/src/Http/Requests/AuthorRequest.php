<?php

namespace Botble\Author\Http\Requests;

use Botble\Base\Enums\BaseStatusEnum;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class AuthorRequest extends Request
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:250'],
            'email' => ['nullable', 'email', 'unique:authors,email,' . $this->route('author.id')],
            'status' => Rule::in(BaseStatusEnum::values()),
        ];
    }
}
