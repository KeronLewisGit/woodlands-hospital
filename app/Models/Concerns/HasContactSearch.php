<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * For submissions with a name, email, phone and reference: keeps a digits-only copy of the
 * phone number (phone_digits) and searches on name, reference, email or 3+ phone digits.
 */
trait HasContactSearch
{
    protected static function bootHasContactSearch(): void
    {
        static::saving(function (self $model): void {
            $model->setAttribute('phone_digits', preg_replace('/\D+/', '', (string) $model->getAttribute('phone')) ?: null);
        });
    }

    /** @param Builder<self> $query */
    protected static function searchContact(Builder $query, string $term): void
    {
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $digits = (string) preg_replace('/\D+/', '', $term);
        $query->where(function (Builder $w) use ($like, $digits) {
            $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like);
            if (strlen($digits) >= 3) {
                $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
            }
        });
    }
}
