<?php

namespace App\Domain\Enums;

enum TicketStatus: string
{
    case Pending = 'pending';
    case ApprovedL1 = 'approved_l1';
    case ApprovedL2 = 'approved_l2';
    case RejectedL1 = 'rejected_l1';
    case RejectedL2 = 'rejected_l2';
    case Delivered = 'delivered';
    case DeliveryFailed = 'delivery_failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::ApprovedL1 => 'Approved by Level 1',
            self::ApprovedL2 => 'Approved by Level 2',
            self::RejectedL1 => 'Rejected by Level 1',
            self::RejectedL2 => 'Rejected by Level 2',
            self::Delivered => 'Delivered',
            self::DeliveryFailed => 'Delivery Failed',
        };
    }

    public function canBeApprovedByLevel1(): bool
    {
        return $this === self::Pending;
    }

    public function canBeApprovedByLevel2(): bool
    {
        return $this === self::ApprovedL1;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::RejectedL1,
            self::RejectedL2,
            self::Delivered,
            self::DeliveryFailed,
        ], true);
    }
}
