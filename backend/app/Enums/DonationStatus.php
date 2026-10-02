<?php

namespace App\Enums;

enum DonationStatus: string
{
    /** Pledged online/mobile; waiting for payment to be verified. */
    case Pending = 'pending';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
