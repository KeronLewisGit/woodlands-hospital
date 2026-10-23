<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobCategory;
use App\Enums\JobStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property JobCategory $category
 * @property EmploymentType $employment_type
 * @property JobStatus $status
 * @property string $location
 * @property int $openings
 * @property Carbon|null $ends_on
 * @property string $summary
 * @property string|null $duties
 * @property string|null $requirements
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class JobPosting extends Model
{
    protected $fillable = ['slug', 'title', 'category', 'employment_type', 'status', 'location', 'openings', 'ends_on', 'summary', 'duties', 'requirements'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'category' => JobCategory::class,
            'employment_type' => EmploymentType::class,
            'status' => JobStatus::class,
            'ends_on' => 'date',
            'openings' => 'integer',
        ];
    }

    #[\Override]
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<JobApplication, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', JobStatus::Open)
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()));
    }

    /** @return list<string> */
    public function lines(string $field): array
    {
        return lines_to_array($this->getAttribute($field));
    }
}
