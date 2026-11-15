<?php

namespace App\Services;

use App\Models\ContentRevision;
use App\Models\HomeDoctor;
use App\Support\RevisionContext;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RollbackService
{
    public function __construct(
        protected ImageUploader $uploader,
    ) {}

    /**
     * Detect conflicts for a given batch.
     * Returns an array of conflict descriptions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function detectConflicts(string $batchId): array
    {
        $revisions = ContentRevision::where('batch_id', $batchId)
            ->orderBy('id', 'desc')
            ->get();

        $conflicts = [];

        foreach ($revisions as $rev) {
            $class = $rev->revisionable_type;
            $id = $rev->revisionable_id;

            if (! class_exists($class)) {
                $conflicts[] = [
                    'revision_id' => $rev->id,
                    'message' => "Class {$class} tidak ditemukan.",
                ];

                continue;
            }

            $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($class));
            $model = $usesSoftDeletes
                ? $class::withTrashed()->find($id)
                : $class::find($id);

            $label = "{$rev->getModelNameLabel()} '{$rev->getRecordLabel()}'";

            switch ($rev->event) {
                case 'created':
                    if (! $model) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} sudah tidak ada di sistem.",
                        ];
                    } elseif (method_exists($model, 'trashed') && $model->trashed()) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} telah dihapus (diarsipkan) setelah batch ini.",
                        ];
                    }
                    break;

                case 'updated':
                    if (! $model) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} tidak ditemukan lagi di database.",
                        ];
                    } else {
                        foreach ($rev->new_values ?? [] as $field => $newVal) {
                            $currentVal = $model->getAttribute($field);
                            if ((string) $currentVal !== (string) $newVal) {
                                $conflicts[] = [
                                    'revision_id' => $rev->id,
                                    'field' => $field,
                                    'message' => "Kolom '{$field}' pada {$label} saat ini bernilai '".Str::limit((string) $currentVal, 25)."', berbeda dari nilai setelah revisi ini ('".Str::limit((string) $newVal, 25)."'). Data telah berubah sejak batch ini.",
                                ];
                            }
                        }
                    }
                    break;

                case 'deleted':
                    if (! $model) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} telah dihapus secara permanen.",
                        ];
                    } elseif (method_exists($model, 'trashed') && ! $model->trashed()) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} saat ini aktif (sudah dipulihkan kembali).",
                        ];
                    }
                    break;

                case 'restored':
                    if ($model && method_exists($model, 'trashed') && $model->trashed()) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "{$label} saat ini sudah dihapus kembali.",
                        ];
                    }
                    break;

                case 'force_deleted':
                    if ($model) {
                        $conflicts[] = [
                            'revision_id' => $rev->id,
                            'message' => "Data dengan ID {$id} sudah ada kembali di database.",
                        ];
                    }
                    break;
            }
        }

        return $conflicts;
    }

    /**
     * Rollback a batch of revisions in reverse order.
     *
     * @return array{success: bool, conflicts: array<int, mixed>, warnings: array<int, string>}
     */
    public function rollback(string $batchId, bool $force = false): array
    {
        $revisions = ContentRevision::where('batch_id', $batchId)
            ->orderBy('id', 'desc')
            ->get();

        if ($revisions->isEmpty()) {
            throw new \InvalidArgumentException("Batch ID '{$batchId}' tidak ditemukan.");
        }

        $conflicts = $this->detectConflicts($batchId);
        if (! empty($conflicts) && ! $force) {
            return [
                'success' => false,
                'conflicts' => $conflicts,
                'warnings' => [],
            ];
        }

        $warnings = [];

        DB::transaction(function () use ($revisions, $batchId, &$warnings) {
            $context = app(RevisionContext::class);
            $context->resetBatchId();
            $context->setMeta(['rolled_back_batch' => $batchId]);

            foreach ($revisions as $rev) {
                $class = $rev->revisionable_type;
                $id = $rev->revisionable_id;

                $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($class));
                $model = $usesSoftDeletes
                    ? $class::withTrashed()->find($id)
                    : $class::find($id);

                switch ($rev->event) {
                    case 'created':
                        if ($model) {
                            $model->delete();
                        }
                        break;

                    case 'updated':
                        if ($model) {
                            // Restore old image if needed
                            if (! empty($rev->old_values) && array_key_exists('image', $rev->old_values)) {
                                $oldImage = $rev->old_values['image'];
                                if ($oldImage && ! $this->uploader->isExternalUrl($oldImage)) {
                                    $folder = ($class === HomeDoctor::class || $class === 'App\Models\HomeDoctor')
                                        ? 'profile'
                                        : 'testimonials';

                                    $restored = $this->uploader->restore($oldImage, $folder);
                                    if (! $restored) {
                                        $warnings[] = "Gambar lama '{$oldImage}' tidak ditemukan di arsip atau sudah dibersihkan.";
                                    }
                                }
                            }

                            $model->update($rev->old_values ?? []);
                        }
                        break;

                    case 'deleted':
                        if ($model && method_exists($model, 'restore')) {
                            $model->restore();
                        }
                        break;

                    case 'restored':
                        if ($model) {
                            $model->delete();
                        }
                        break;

                    case 'force_deleted':
                        if (! $model && ! empty($rev->old_values)) {
                            $newModel = new $class;
                            $newModel->forceFill($rev->old_values);
                            $newModel->save();
                        }
                        break;
                }
            }
        });

        return [
            'success' => true,
            'conflicts' => [],
            'warnings' => $warnings,
        ];
    }
}
