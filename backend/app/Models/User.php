<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** Mirror the column default so new instances have a role before being refreshed. */
    protected $attributes = ['role' => 'member'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function masjid(): BelongsTo
    {
        return $this->belongsTo(Masjid::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Where this user lands after signing in: staff get the admin dashboard,
     * members get their own donations page.
     */
    public function homeUrl(): string
    {
        return $this->masjid_id && $this->role->canManageFinance() ? route('admin.dashboard') : route('account');
    }

    public function isAdminOf(Masjid $masjid): bool
    {
        return $this->role === Role::Admin && (int) $this->masjid_id === $masjid->id;
    }

    public function canManageFinanceOf(Masjid $masjid): bool
    {
        return $this->role->canManageFinance() && (int) $this->masjid_id === $masjid->id;
    }
}
