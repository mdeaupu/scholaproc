<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProcurementVerificationFile extends Model
{
    public const TYPE_PHOTO = 'photo';

    public const TYPE_SIGNED_DOCUMENT = 'signed_document';

    public $timestamps = false;

    protected $fillable = [
        'procurement_request_id',
        'file_type',
        'file_path',
        'uploaded_by',
        'notes',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isPhoto(): bool
    {
        return $this->file_type === self::TYPE_PHOTO;
    }

    public function isSignedDocument(): bool
    {
        return $this->file_type === self::TYPE_SIGNED_DOCUMENT;
    }
}
