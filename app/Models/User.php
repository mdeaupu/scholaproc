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
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

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
        'phone',
        'notes',
        'avatar_path',
        'password',
        'status',
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

    public function negotiations(): HasMany
    {
        return $this->hasMany(ProcurementNegotiation::class);
    }

    public function verifiedRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class, 'verified_by');
    }

    public function reviewedRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class, 'reviewed_by');
    }

    public function verificationFiles(): HasMany
    {
        return $this->hasMany(ProcurementVerificationFile::class, 'uploaded_by');
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isSchool(): bool
    {
        return $this->role === 'school';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canLogin(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if (!$this->isSchool()) {
            return true;
        }

        return $this->school !== null && $this->school->isActive();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function deactivate(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function terminateSessions(): void
    {
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $this->getKey())
                ->delete();
        }
    }

    public function resetPassword(string $newPassword): void
    {
        $this->update(['password' => $newPassword]);
    }

    public function canManageSchools(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canManageSubsidiaries(): bool
    {
        return $this->isSuperAdmin();
    }

    public function canProcessProcurement(): bool
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }

    public function hasTransactionalHistory(): bool
    {
        return $this->histories()->exists()
            || $this->negotiations()->exists()
            || $this->verifiedRequests()->exists()
            || $this->verificationFiles()->exists();
    }

    public function canDecideNegotiation(ProcurementRequest $request): bool
    {
        return $this->isSchool() && $this->school_id === $request->school_id;
    }

    public function canVerifyReceipt(ProcurementRequest $request): bool
    {
        return $this->isSchool() && $this->school_id === $request->school_id;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->where('role', 'superadmin');
    }

    public function scopeAdmin(Builder $query): Builder
    {
        return $query->where('role', 'admin');
    }

    public function scopeSchool(Builder $query): Builder
    {
        return $query->where('role', 'school');
    }
}
