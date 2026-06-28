<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'school_id',
        'username',
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

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
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProcurementRequestHistory::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
    public function isAdminCv(): bool
    {
        return $this->role === 'admin_cv';
    }
    public function isAdminSchool(): bool
    {
        return $this->role === 'admin_school';
    }

    public function canManageSchools(): bool
    {
        return $this->isOwner();
    }
    public function canManageSuppliers(): bool
    {
        return $this->isOwner();
    }
    public function canProcessProcurement(): bool
    {
        return $this->isAdminCv();
    }

    public function scopeOwner(Builder $query): Builder
    {
        return $query->where('role', 'owner');
    }
    public function scopeAdminCv(Builder $query): Builder
    {
        return $query->where('role', 'admin_cv');
    }
    public function scopeAdminSchool(Builder $query): Builder
    {
        return $query->where('role', 'admin_school');
    }
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'suspended');
    }

    public static function createAdminCv(array $data): self
    {
        return self::create([
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => empty($data['email']) ? null : $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'admin_cv',
            'status' => 'active',
            'school_id' => null,
        ]);
    }

    public function updateAdminCv(array $data): void
    {
        $updateData = [
            'username' => $data['username'],
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $this->update($updateData);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function resetPassword(string $newPassword = 'Password123!'): void
    {
        $this->update(['password' => Hash::make($newPassword)]);
    }

    public function changePassword(string $newPassword): void
    {
        $this->update(['password' => Hash::make($newPassword)]);
    }
}
