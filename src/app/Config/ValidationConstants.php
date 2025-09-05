<?php
declare(strict_types=1);

namespace App\Config;

class ValidationConstants
{
    public const VALID_PLATFORMS = ['android' => true, 'ios' => true];
    public const SEMVER_PATTERN = '/^\d+\.\d+\.\d+$/';
}
