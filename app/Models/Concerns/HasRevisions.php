<?php

namespace App\Models\Concerns;

use App\Models\ContentRevision;
use App\Support\RevisionContext;
use Illuminate\Database\Eloquent\SoftDeletes;

trait HasRevisions
{
    public static function bootHasRevisions(): void
    {
        static::created(function ($model) {
            $context = app(RevisionContext::class);
            if (! $context->isRecording()) {
                return;
            }

            $except = $model->getRevisionExcept();
            $newValues = array_diff_key($model->getAttributes(), array_flip($except));

            ContentRevision::create([
                'batch_id' => $context->batchId(),
                'user_id' => $context->userId(),
                'revisionable_type' => $model->getMorphClass(),
                'revisionable_id' => (string) $model->getKey(),
                'event' => 'created',
                'old_values' => null,
                'new_values' => $newValues,
                'meta' => $context->getMeta() ?: null,
                'ip_address' => $context->ip(),
                'created_at' => now(),
            ]);
        });

        static::updated(function ($model) {
            $context = app(RevisionContext::class);
            if (! $context->isRecording()) {
                return;
            }

            $except = $model->getRevisionExcept();
            $changes = array_diff_key($model->getChanges(), array_flip($except));

            // Don't record revision if no real change
            if (empty($changes)) {
                return;
            }

            $oldValues = [];
            $newValues = [];

            foreach ($changes as $key => $newValue) {
                $oldValues[$key] = $model->getOriginal($key);
                $newValues[$key] = $newValue;
            }

            ContentRevision::create([
                'batch_id' => $context->batchId(),
                'user_id' => $context->userId(),
                'revisionable_type' => $model->getMorphClass(),
                'revisionable_id' => (string) $model->getKey(),
                'event' => 'updated',
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'meta' => $context->getMeta() ?: null,
                'ip_address' => $context->ip(),
                'created_at' => now(),
            ]);
        });

        static::deleted(function ($model) {
            $context = app(RevisionContext::class);
            if (! $context->isRecording()) {
                return;
            }

            $isForce = method_exists($model, 'isForceDeleting')
                ? $model->isForceDeleting()
                : true;

            $event = $isForce ? 'force_deleted' : 'deleted';

            $except = $model->getRevisionExcept();
            $oldValues = array_diff_key($model->getAttributes(), array_flip($except));

            ContentRevision::create([
                'batch_id' => $context->batchId(),
                'user_id' => $context->userId(),
                'revisionable_type' => $model->getMorphClass(),
                'revisionable_id' => (string) $model->getKey(),
                'event' => $event,
                'old_values' => $oldValues,
                'new_values' => null,
                'meta' => $context->getMeta() ?: null,
                'ip_address' => $context->ip(),
                'created_at' => now(),
            ]);
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                $context = app(RevisionContext::class);
                if (! $context->isRecording()) {
                    return;
                }

                $except = $model->getRevisionExcept();
                $newValues = array_diff_key($model->getAttributes(), array_flip($except));

                ContentRevision::create([
                    'batch_id' => $context->batchId(),
                    'user_id' => $context->userId(),
                    'revisionable_type' => $model->getMorphClass(),
                    'revisionable_id' => (string) $model->getKey(),
                    'event' => 'restored',
                    'old_values' => null,
                    'new_values' => $newValues,
                    'meta' => $context->getMeta() ?: null,
                    'ip_address' => $context->ip(),
                    'created_at' => now(),
                ]);
            });
        }
    }

    /**
     * Attributes excluded from revision tracking.
     *
     * @return array<int, string>
     */
    public function getRevisionExcept(): array
    {
        return property_exists($this, 'revisionExcept')
            ? $this->revisionExcept
            : ['created_at', 'updated_at', 'deleted_at'];
    }
}
