<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'npsn',
        'name',
        'address',
        'postal_code',
        'phone_number',
        'email',
        'status',
    ];

    public function setting(): HasOne
    {
        return $this->hasOne(SchoolSetting::class);
    }

    public function account(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'school');
    }
    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function documentNumberSequences(): HasMany
    {
        return $this->hasMany(DocumentNumberSequence::class);
    }

    public function activeRequestsCount(): int
    {
        return $this->procurementRequests()
            ->whereNotIn('status', ['completed', 'rejected'])
            ->count();
    }

    public function completedRequestsCount(): int
    {
        return $this->procurementRequests()
            ->where('status', 'completed')
            ->count();
    }

    public function activate(): bool
    {
        return $this->update(['status' => 'active']);
    }

    public function suspend(): bool
    {
        return $this->update(['status' => 'suspended']);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function totalRequests(): int
    {
        return $this->procurementRequests()->count();
    }
    public function totalProcurementValue(): float
    {
        return (float) $this->procurementRequests()
            ->where('status', 'completed')
            ->sum('grand_total') ?? 0.00;
    }
}
