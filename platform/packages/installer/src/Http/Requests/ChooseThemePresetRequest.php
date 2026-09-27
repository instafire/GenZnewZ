<?php

namespace Botble\Installer\Http\Requests;

use Botble\Installer\InstallerStep\InstallerStep;
use Botble\Support\Http\Requests\Request;
use Illuminate\Validation\Rule;

class ChooseThemePresetRequest extends Request
{
    public function rules(): array
    {
        return [
            'theme_preset' => ['required', 'string', Rule::in(array_keys(InstallerStep::getThemePresets()))],
        ];
    }
}
