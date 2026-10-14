<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A member of the masjid committee, listed on the public site.
 */
#[Fillable(['name', 'role', 'title', 'phone', 'show_phone', 'sort_order', 'is_active'])]
class CommitteeMember extends Model
{
    /** Roles in the order they are listed; office bearers first. */
    public const ROLES = [
        'mutawalli' => 'Mutawalli',
        'president' => 'President',
        'vice_president' => 'Vice President',
        'secretary' => 'Secretary',
        'joint_secretary' => 'Joint Secretary',
        'treasurer' => 'Treasurer',
        'member' => 'Member',
        'other' => 'Other',
    ];

    public const OFFICE_BEARERS = ['mutawalli', 'president', 'vice_president', 'secretary', 'joint_secretary', 'treasurer'];

    protected function casts(): array
    {
        return [
            'show_phone' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    /**
     * The role in the current language; for "other" it is the title the admin typed.
     */
    public function roleLabel(): string
    {
        if ($this->role === 'other' && $this->title) {
            return $this->title;
        }

        return __(self::ROLES[$this->role] ?? self::ROLES['member']);
    }


    public function roleRank(): int
    {
        return (int) array_search($this->role, array_keys(self::ROLES), true);
    }
}
