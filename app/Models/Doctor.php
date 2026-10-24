<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Doctor directory entry, managed by staff (seeded empty: the hospital supplies the list).
 *
 * @property int $id
 * @property string $name
 * @property string|null $title
 * @property string $department
 * @property string|null $qualifications
 * @property string|null $bio
 * @property bool $is_published
 * @property int $sort
 */
class Doctor extends Model
{
    protected $fillable = ['name', 'title', 'department', 'qualifications', 'bio', 'is_published', 'sort'];

    #[\Override]
    protected function casts(): array
    {
        return ['is_published' => 'boolean', 'sort' => 'integer'];
    }

    /** @param Builder<self> $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @return HasMany<ClinicSession, $this> */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClinicSession::class);
    }

    public function departmentName(): string
    {
        return (string) (department($this->department)['name'] ?? $this->department);
    }

    /** Two-letter monogram used instead of a stock portrait. */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) preg_replace('/^(dr\.?|prof\.?|mr\.?|mrs\.?|ms\.?)\s+/i', '', $this->name))) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr((string) end($parts), 0, 1) : '';

        return mb_strtoupper($first.$last);
    }
}
