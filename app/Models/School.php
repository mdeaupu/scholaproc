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

    public function admin(): HasOne
    {
        return $this->hasOne(User::class)->where('role', 'admin_school');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
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
            ->completed()
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
            ->join('procurement_request_items', 'procurement_requests.id', '=', 'procurement_request_items.procurement_request_id')
            ->where('procurement_requests.status', 'completed')
            ->sum('procurement_request_items.official_price') ?? 0.00;
    }
}
