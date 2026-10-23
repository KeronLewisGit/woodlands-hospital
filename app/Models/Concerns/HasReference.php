<?php

namespace App\Models\Concerns;

/**
 * A short human reference like "WA-26-00042", read out over the phone.
 * The model must define REFERENCE_PREFIX.
 */
trait HasReference
{
    protected static function bootHasReference(): void
    {
        static::creating(function (self $model): void {
            $model->reference ??= 'pending-'.bin2hex(random_bytes(4));
        });
        static::created(function (self $model): void {
            $model->forceFill([
                'reference' => sprintf('%s-%s-%05d', static::REFERENCE_PREFIX, $model->created_at->format('y'), $model->id),
            ])->saveQuietly();
        });
    }
}
