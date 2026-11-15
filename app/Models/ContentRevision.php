<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'content_revisions';

    protected $fillable = [
        'batch_id',
        'user_id',
        'revisionable_type',
        'revisionable_id',
        'event',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable Indonesian model name.
     */
    public function getModelNameLabel(): string
    {
        return match ($this->revisionable_type) {
            HomeDoctor::class, 'App\Models\HomeDoctor' => 'Dokter',
            HomeCard::class, 'App\Models\HomeCard' => 'Kartu Layanan',
            HomeTestimony::class, 'App\Models\HomeTestimony' => 'Testimoni',
            HomeText::class, 'App\Models\HomeText' => 'Teks Beranda',
            default => class_basename($this->revisionable_type),
        };
    }

    /**
     * Descriptive label of the revisionable record.
     */
    public function getRecordLabel(): string
    {
        $values = $this->new_values ?: $this->old_values ?: [];

        if (! empty($values['name'])) {
            return (string) $values['name'];
        }

        if (! empty($values['title'])) {
            return (string) $values['title'];
        }

        if ($this->revisionable_type === HomeText::class || $this->revisionable_type === 'App\Models\HomeText') {
            return (string) $this->revisionable_id;
        }

        return '#'.$this->revisionable_id;
    }

    /**
     * Indonesian event badge label.
     */
    public function getEventBadge(): string
    {
        return match ($this->event) {
            'created' => 'Dibuat',
            'updated' => 'Diperbarui',
            'deleted' => 'Dihapus (Arsip)',
            'restored' => 'Dipulihkan',
            'force_deleted' => 'Dihapus Permanen',
            default => ucfirst($this->event),
        };
    }
}
