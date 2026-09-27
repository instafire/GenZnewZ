<?php

namespace Botble\ACL\Http\Requests;

use Botble\ACL\Models\User;
use Botble\Base\Facades\BaseHelper;
use Botble\Base\Rules\EmailRule;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class CreateUserRequest extends Request
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:60', 'min:2'],
            'last_name' => ['required', 'string', 'max:60', 'min:2'],
            'email' => [
                'required',
                'min:6',
                'max:120',
                new EmailRule(),
                Rule::unique((new User())->getTable(), 'email'),
            ],
            'password' => ['required', 'string', 'min:6', 'max:120', 'confirmed'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:120',
                Rule::unique((new User())->getTable(), 'username'),
            ],
            'phone' => ['nullable', ...BaseHelper::getPhoneValidationRule(true)],
        ];
    }
}
