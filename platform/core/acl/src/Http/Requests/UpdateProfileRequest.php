<?php

namespace Botble\ACL\Http\Requests;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Rules\EmailRule;
use Botble\Support\Http\Requests\Request;

class UpdateProfileRequest extends Request
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:120'],
            'first_name' => ['required', 'string', 'max:60', 'min:2'],
            'last_name' => ['required', 'string', 'max:60', 'min:2'],
            'email' => ['required', 'max:120', 'min:6', new EmailRule()],
            'phone' => ['nullable', ...BaseHelper::getPhoneValidationRule(true)],
        ];
    }
}
