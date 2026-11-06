<?php

namespace App\Console\Commands;

use App\Models\ProcurementDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackfillDocumentLetterheads extends Command
{
    protected $signature = 'documents:backfill-letterheads';

    protected $description = 'Embed historical subsidiary letterheads in procurement document snapshots';

    public function handle(): int
    {
        $total = ProcurementDocument::query()->count();
        $processed = 0;
        $updated = 0;
        $unresolved = 0;
        $unresolvedIds = [];
        $progress = $this->output->createProgressBar($total);
        $progress->start();

        ProcurementDocument::query()
            ->with(['procurementRequest.subsidiary'])
            ->chunkById(100, function ($documents) use (
                &$processed,
                &$updated,
                &$unresolved,
                &$unresolvedIds,
                $progress
            ): void {
                foreach ($documents as $document) {
                    $snapshot = $document->document_snapshot ?? [];
                    $identity = data_get($snapshot, 'subsidiary', []);
                    if (! is_array($identity)) {
                        $identity = [];
                    }
                    $encoded = data_get($identity, 'letterhead_base64');
                    $mimeType = data_get($identity, 'letterhead_mime_type');

                    if ($this->hasValidEmbeddedImage($encoded, $mimeType)) {
                        $processed++;
                        $progress->advance();

                        continue;
                    }

                    $letterhead = $this->readLetterhead($document);
                    if ($letterhead === null) {
                        $unresolved++;
                        if (count($unresolvedIds) < 20) {
                            $unresolvedIds[] = $document->id;
                        }
                        $processed++;
                        $progress->advance();

                        continue;
                    }

                    if ($letterhead['path'] !== '') {
                        data_set($snapshot, 'subsidiary.letterhead_path', $letterhead['path']);
                    }
                    data_set($snapshot, 'subsidiary.letterhead_base64', $letterhead['base64']);
                    data_set($snapshot, 'subsidiary.letterhead_mime_type', $letterhead['mime_type']);

                    DB::table($document->getTable())
                        ->where($document->getKeyName(), $document->getKey())
                        ->update([
                            'document_snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                            'updated_at' => now(),
                        ]);

                    $updated++;
                    $processed++;
                    $progress->advance();
                }
            });

        $progress->finish();
        $this->newLine(2);
        $this->info("Processed {$processed} procurement documents.");
        $this->info("Backfilled {$updated} document snapshots.");

        if ($unresolved > 0) {
            $this->warn("Could not locate a valid stored letterhead for {$unresolved} documents.");
            $this->line('Unresolved document IDs (up to 20): '.implode(', ', $unresolvedIds));

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function hasValidEmbeddedImage(mixed $encoded, mixed $mimeType): bool
    {
        if (! is_string($encoded)
            || $encoded === ''
            || ! is_string($mimeType)
            || ! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            return false;
        }

        $decoded = base64_decode($encoded, true);

        return is_string($decoded) && $decoded !== '';
    }

    /**
     * @return array{path:string, base64:string, mime_type:string}|null
     */
    private function readLetterhead(ProcurementDocument $document): ?array
    {
        $request = $document->procurementRequest;
        $documentSnapshot = $document->document_snapshot ?? [];
        $snapshotIdentity = data_get($documentSnapshot, 'subsidiary', []);
        $frozenIdentity = $document->subsidiary_snapshot ?? [];
        $frozenBase64 = data_get($frozenIdentity, 'letterhead_base64');
        $frozenMimeType = data_get($frozenIdentity, 'letterhead_mime_type');

        if ($this->hasValidEmbeddedImage($frozenBase64, $frozenMimeType)) {
            return [
                'path' => data_get($snapshotIdentity, 'letterhead_path')
                    ?? data_get($frozenIdentity, 'letterhead_path', ''),
                'base64' => $frozenBase64,
                'mime_type' => $frozenMimeType,
            ];
        }

        $paths = [
            data_get($documentSnapshot, 'subsidiary.letterhead_path'),
            data_get($documentSnapshot, 'letterhead_path'),
            data_get($documentSnapshot, 'metadata.letterhead_path'),
            data_get($documentSnapshot, 'meta.letterhead_path'),
            data_get($document->subsidiary_snapshot, 'letterhead_path'),
            data_get($request?->subsidiary_snapshot, 'letterhead_path'),
            $request?->subsidiary?->letterhead_path,
        ];
        $disk = Storage::disk('public');

        foreach (array_unique(array_filter($paths, 'is_string')) as $path) {
            if ($path === '' || ! $disk->exists($path)) {
                continue;
            }

            $mimeType = $disk->mimeType($path);
            if (! in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                continue;
            }

            $contents = $disk->get($path);
            if ($contents === '') {
                continue;
            }

            return [
                'path' => $path,
                'base64' => base64_encode($contents),
                'mime_type' => $mimeType,
            ];
        }

        return null;
    }
}
