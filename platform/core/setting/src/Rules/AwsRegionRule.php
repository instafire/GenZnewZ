<?php

namespace Botble\Setting\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AwsRegionRule implements ValidationRule
{
    /**
     * AWS region pattern based on official AWS region naming convention.
     * Supports all current AWS partitions and regions including:
     * - Standard AWS regions (us-east-1, eu-west-1, etc.)
     * - China regions (cn-north-1, cn-northwest-1)
     * - GovCloud regions (us-gov-east-1, us-gov-west-1)
     * - ISO regions (us-iso-east-1, us-iso-west-1)
     * - ISOB regions (us-isob-east-1)
     * - Israel regions (il-central-1)
     */
    private const AWS_REGION_PATTERN = '/^(af|il|ap|ca|eu|me|sa|us|cn|us-gov|us-iso|us-isob)-(central|north|(north(?:east|west))|south|south(?:east|west)|east|west)-\d+$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(trans('validation.string'));

            return;
        }

        // Check if the region matches the AWS region pattern
        if (! preg_match(self::AWS_REGION_PATTERN, $value)) {
            $fail(trans('core/setting::setting.validation.aws_region_invalid'));

            return;
        }

        // Additional validation using AWS SDK's host label validation logic
        // Each part of the region (separated by hyphens) should be a valid host label
        $parts = explode('-', $value);
        foreach ($parts as $part) {
            if (! $this->isValidHostLabel($part)) {
                $fail(trans('core/setting::setting.validation.aws_region_invalid'));

                return;
            }
        }
    }

    /**
     * Validates if a string is a valid host label based on AWS SDK's is_valid_hostlabel function.
     *
     * @param string $label
     * @return bool
     */
    private function isValidHostLabel(string $label): bool
    {
        // Based on AWS SDK's is_valid_hostlabel function:
        // - Must be 1-63 characters long
        // - Can contain letters, numbers, and hyphens
        // - Cannot start or end with a hyphen
        return preg_match('/^(?!-)[a-zA-Z0-9-]{1,63}(?<!-)$/', $label) === 1;
    }
}
