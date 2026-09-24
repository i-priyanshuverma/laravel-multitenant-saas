<?php

namespace App\Enums;

enum TenantStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Canceled = 'canceled';

    /**
     * Get all values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
