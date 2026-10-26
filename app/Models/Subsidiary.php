<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use LogicException;

class Subsidiary extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    private const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    private const AUDITED_ATTRIBUTES = [
        'name',
        'npwp',
        'director_name',
        'pic_name',
        'email',
        'phone',
        'address',
        'nib',
        'director_nik',
        'director_npwp',
        'director_phone',
        'director_address',
        'commissioner_name',
        'commissioner_nik',
        'commissioner_address',
        'status',
    ];

    private array $auditBefore = [];

    protected $attributes = [
        'status' => 'active',
    ];

    protected $fillable = [
        'name',
        'pic_name',
        'email',
        'phone',
        'address',
        'npwp',
        'nib',
        'director_name',
        'director_nik',
        'director_npwp',
        'director_phone',
        'director_address',
        'commissioner_name',
        'commissioner_nik',
        'commissioner_address',
        'status',
    ];

    public function legalDocuments(): HasMany
    {
        return $this->hasMany(SubsidiaryLegalDocument::class);
    }

    public function procurementRequests(): HasMany
    {
        return $this->hasMany(ProcurementRequest::class);
    }

    public function activeBusinessPermit(): ?SubsidiaryLegalDocument
    {
        return $this->legalDocuments()
            ->where('document_type', DocumentType::BUSINESS_PERMIT)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', now());
            })
            ->first();
    }

    public function totalProjects(): int
    {
        return $this->procurementRequests()->count();
    }

    public function totalProjectValue(): float|int
    {
        $procurements = $this->procurementRequests()->with('items')->get();

        return $procurements->sum(function ($procurement) {
            return $procurement->items->sum(function ($item) {
                $price = $item->official_price ?? $item->estimated_price;

                return $price * $item->quantity;
            });
        });
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function changeStatus(string $status): void
    {
        Validator::make(
            ['status' => $status],
            ['status' => ['required', Rule::in(self::STATUSES)]]
        )->validate();

        $this->update(['status' => $status]);
    }

    public function documentIdentitySnapshot(): array
    {
        return [
            'name' => $this->name,
            'npwp' => $this->npwp,
            'director_name' => $this->director_name,
            'letterhead_layout' => 'subsidiary-letterhead',
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function canBeDeleted(): bool
    {
        return ! $this->procurementRequests()->exists()
            && ! $this->legalDocuments()->exists()
            && ! ProcurementDocument::query()->where('subsidiary_id', $this->id)->exists();
    }

    protected static function booted(): void
    {
        static::saving(function (self $subsidiary): void {
            if (! in_array($subsidiary->status, self::STATUSES, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status Anak Perusahaan harus aktif atau nonaktif.',
                ]);
            }
        });

        static::created(function (self $subsidiary): void {
            $subsidiary->recordAudit(
                'created',
                [],
                array_intersect_key($subsidiary->getAttributes(), array_flip(self::AUDITED_ATTRIBUTES))
            );
        });

        static::updating(function (self $subsidiary): void {
            $dirty = array_intersect_key(
                $subsidiary->getDirty(),
                array_flip(self::AUDITED_ATTRIBUTES)
            );

            $subsidiary->auditBefore = array_intersect_key($subsidiary->getOriginal(), $dirty);
        });

        static::updated(function (self $subsidiary): void {
            $after = array_intersect_key($subsidiary->getChanges(), $subsidiary->auditBefore);

            if ($after === []) {
                return;
            }

            $before = array_intersect_key($subsidiary->auditBefore, $after);
            $event = array_key_exists('status', $after) ? 'status_updated' : 'updated';

            $subsidiary->recordAudit($event, $before, $after);
            $subsidiary->auditBefore = [];
        });

        static::deleting(function (self $subsidiary): void {
            if (! $subsidiary->canBeDeleted()) {
                throw new LogicException('Anak Perusahaan yang memiliki transaksi atau dokumen tidak dapat dihapus.');
            }

            $subsidiary->recordAudit(
                'deleted',
                array_intersect_key($subsidiary->getAttributes(), array_flip(self::AUDITED_ATTRIBUTES)),
                []
            );
        });
    }

    private function recordAudit(string $event, array $before, array $after): void
    {
        activity('subsidiaries')
            ->performedOn($this)
            ->causedBy(auth()->user())
            ->withProperties([
                'event' => $event,
                'before' => $before,
                'after' => $after,
                'changed_at' => now()->toIso8601String(),
            ])
            ->log(match ($event) {
                'created' => 'Anak Perusahaan ditambahkan',
                'status_updated' => 'Status Anak Perusahaan diubah',
                'deleted' => 'Anak Perusahaan dihapus',
                default => 'Data Anak Perusahaan diubah',
            });
    }
}
