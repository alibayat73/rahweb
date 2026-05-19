<?php

namespace App\Domain\Enums;

enum AdminRole: string
{
    case User = 'user';
    case AdminL1 = 'admin_l1';
    case AdminL2 = 'admin_l2';

    public function label(): string
    {
        return match ($this) {
            self::User => 'User',
            self::AdminL1 => 'Admin Level 1',
            self::AdminL2 => 'Admin Level 2',
        };
    }

    public function isAdmin(): bool
    {
        return in_array($this, [self::AdminL1, self::AdminL2], true);
    }
}
