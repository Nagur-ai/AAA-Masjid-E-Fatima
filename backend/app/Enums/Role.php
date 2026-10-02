<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Treasurer = 'treasurer';
    case Member = 'member';

    public const PANEL_SUMMARY = 'summary';

    public const PANEL_PENDING = 'pending';

    public const PANEL_FUNDS = 'funds';

    public const PANEL_DONATIONS = 'donations';

    public const PANEL_EXPENSES = 'expenses';

    public const PANEL_PRAYERS = 'prayers';

    public const PANEL_RAMADAN = 'ramadan';

    public const PANEL_STAFF = 'staff';

    public function canManageFinance(): bool
    {
        return in_array($this, [self::Admin, self::Treasurer], true);
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * Admin dashboard panels this role sees, in display order. Members have
     * no admin dashboard; they get their own donations page instead.
     *
     * @return list<string>
     */
    public function dashboardPanels(): array
    {
        $finance = [self::PANEL_SUMMARY, self::PANEL_PENDING, self::PANEL_FUNDS, self::PANEL_DONATIONS, self::PANEL_EXPENSES];

        return match ($this) {
            self::Admin => [...$finance, self::PANEL_PRAYERS, self::PANEL_RAMADAN, self::PANEL_STAFF],
            self::Treasurer => $finance,
            self::Member => [],
        };
    }
}
