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
}
