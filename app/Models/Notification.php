<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;
    public const CHANNEL_WHATSAPP = 'whatsapp';
    public const CHANNEL_EMAIL = 'email';

    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    public const SCHOOL_EVENTS = [
        'verified',
        'rejected',
        'supplier_assigned',
        'negotiation_offer',
        'negotiation_accepted',
        'negotiation_rejected',
        'items_prepared',
        'completed',
        'document_generated',
    ];

    protected $fillable = [
        'procurement_request_id',
        'event_type',
        'triggered_by',
        'channel',
        'recipient',
        'message_content',
        'status',
        'error_message',
        'sent_at',
        'read_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // ─── Relationships ──────────────────────────────────────────────

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // ─── Scopes ─────────────────────────────────────────────────────

    public function scopeByChannel(Builder $query, string $channel): Builder
    {
        return $query->where('channel', $channel);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeForProcurement(Builder $query, int $procurementRequestId): Builder
    {
        return $query->where('procurement_request_id', $procurementRequestId);
    }

    public function scopeByEventType(Builder $query, string $eventType): Builder
    {
        return $query->where('event_type', $eventType);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    // ─── Helper Methods ─────────────────────────────────────────────

    public function markSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markFailed(string $error = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $error,
        ]);
    }

    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function isWhatsapp(): bool
    {
        return $this->channel === self::CHANNEL_WHATSAPP;
    }

    public function isEmail(): bool
    {
        return $this->channel === self::CHANNEL_EMAIL;
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_SENT => 'Terkirim',
            self::STATUS_FAILED => 'Gagal',
            default => $this->status,
        };
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            self::CHANNEL_WHATSAPP => 'WhatsApp',
            self::CHANNEL_EMAIL => 'Email',
            default => $this->channel,
        };
    }
}
