<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\JobCategory;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property int|null $job_posting_id
 * @property JobCategory $category
 * @property ApplicationStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property string $phone
 * @property string $email
 * @property string|null $registration
 * @property int|null $years_experience
 * @property string|null $message
 * @property string|null $cv_path
 * @property string|null $cv_name
 * @property string|null $staff_notes
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read JobPosting|null $jobPosting
 */
class JobApplication extends Model
{
    use HasReference;

    public const string REFERENCE_PREFIX = 'WJ';

    public const array CV_TYPES = ['pdf', 'doc', 'docx'];

    protected $fillable = [
        'job_posting_id', 'category', 'status', 'first_name', 'last_name', 'phone', 'email', 'registration',
        'years_experience', 'message', 'cv_path', 'cv_name', 'staff_notes', 'status_changed_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'category' => JobCategory::class,
            'status' => ApplicationStatus::class,
            'years_experience' => 'integer',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (self $application): void {
            $application->phone_digits = preg_replace('/\D+/', '', (string) $application->phone) ?: null;
        });
    }

    /** @return BelongsTo<JobPosting, $this> */
    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function moveTo(ApplicationStatus $status): void
    {
        if ($this->status !== $status) {
            $this->status = $status;
            $this->status_changed_at = now();
        }
    }

    /** @param Builder<self> $query */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', ApplicationStatus::New);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, string|null>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn (Builder $q, $v) => $q->where('category', $v))
            ->when($filters['job'] ?? null, fn (Builder $q, $v) => $q->where('job_posting_id', $v))
            ->when($filters['q'] ?? null, function (Builder $q, $term) {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], (string) $term).'%';
                $digits = preg_replace('/\D+/', '', (string) $term);
                $q->where(function (Builder $w) use ($like, $digits) {
                    $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('reference', 'like', $like)->orWhere('email', 'like', $like);
                    if (strlen((string) $digits) >= 3) {
                        $w->orWhere('phone_digits', 'like', '%'.$digits.'%');
                    }
                });
            });
    }
}
