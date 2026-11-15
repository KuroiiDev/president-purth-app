<?php

namespace App\Support;

use Illuminate\Support\Str;

class RevisionContext
{
    protected ?string $batchId = null;

    protected bool $recording = true;

    protected array $meta = [];

    /**
     * Lazy UUID per request or process.
     */
    public function batchId(): string
    {
        if ($this->batchId === null) {
            $this->batchId = (string) Str::uuid();
        }

        return $this->batchId;
    }

    public function setBatchId(?string $batchId): self
    {
        $this->batchId = $batchId;

        return $this;
    }

    public function resetBatchId(): self
    {
        $this->batchId = null;

        return $this;
    }

    public function userId(): ?int
    {
        return auth()->id();
    }

    public function ip(): ?string
    {
        return request()?->ip();
    }

    public function isRecording(): bool
    {
        return $this->recording;
    }

    /**
     * Execute callback without recording content revisions.
     */
    public function withoutRecording(callable $callback): mixed
    {
        $previous = $this->recording;
        $this->recording = false;

        try {
            return $callback();
        } finally {
            $this->recording = $previous;
        }
    }

    public function setMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);

        return $this;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function clearMeta(): self
    {
        $this->meta = [];

        return $this;
    }
}
