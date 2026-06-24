<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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

    // --- AUTHORIZATION METHODS (Business Rules di Model) ---
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

    // --- QUERY SCOPES ---
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
}
