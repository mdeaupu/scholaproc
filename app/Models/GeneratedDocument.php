<?php

namespace App\Models;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class GeneratedDocument extends Model implements HasMedia
{
    use InteractsWithMedia, HasFactory;

    protected $fillable = [
        'procurement_request_id',
        'document_type',
        'file_path',
        'download_token',
    ];

    protected function casts(): array
    {
        return [
        ];
    }

    public function procurementRequest(): BelongsTo
    {
        return $this->belongsTo(ProcurementRequest::class);
    }

    public static function generatePdf(ProcurementRequest $procurement, string $type, string $viewName, array $data): self
    {
        $pdf = Pdf::loadView($viewName, $data);
        $fileName = "{$type}_{$procurement->uuid}.pdf";

        $tempPath = 'temp/' . $fileName;
        Storage::put($tempPath, $pdf->output());

        return self::create([
            'procurement_request_id' => $procurement->id,
            'document_type' => $type,
            'file_path' => $tempPath,
            'download_token' => Str::random(64),
        ]);
    }

    public function regeneratePdf(): self
    {
        $procurement = $this->procurementRequest;
        $type = $this->document_type;

        $this->clearMediaCollection('documents');
        $this->delete();

        $method = 'generate' . str($type)->camel()->ucfirst();
        return $procurement->$method();
    }

    public function downloadUrl(): string
    {
        /** @var Media|null $media */
        $media = $this->media()->where('collection_name', 'documents')->first();

        if ($media) {
            return $media->getPath();
        }

        if (file_exists($this->file_path)) {
            return $this->file_path;
        }

        return Storage::disk('local')->path($this->file_path);
    }
}

