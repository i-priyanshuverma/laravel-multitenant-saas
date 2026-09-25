<?php

namespace App\Enums;

enum UserRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * Get all values as an array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isOwner(): bool
    {
        return $this === self::Owner;
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isElevated(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Workspace Owner',
            self::Admin => 'Administrator',
            self::Member => 'Team Member',
            self::Viewer => 'Viewer',
        };
    }
}
