<?php

namespace App\Models;

use App\Enums\AppointmentService;
use App\Enums\AppointmentStatus;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A request for an appointment. Nothing is booked until staff confirm a time.
 *
 * @property int $id
 * @property string $reference
 * @property AppointmentService $service
 * @property string|null $department
 * @property int|null $doctor_id
 * @property AppointmentStatus $status
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property string $phone
 * @property string|null $email
 * @property bool $returning_patient
 * @property Carbon $preferred_date
 * @property string $preferred_window
 * @property Carbon|null $alternate_date
 * @property string|null $reason
 * @property bool $has_referral
 * @property Carbon|null $scheduled_for
 * @property string|null $message_to_patient
 * @property string|null $staff_notes
 * @property int|null $handled_by
 * @property Carbon|null $status_changed_at
 * @property Carbon $created_at
 * @property-read string $full_name
 * @property-read Doctor|null $doctor
 * @property-read User|null $handler
 */
class AppointmentRequest extends Model
{
    use HasReference;

    public const string REFERENCE_PREFIX = 'WA';

    public const array WINDOWS = [
        'morning' => 'Morning (8 am – 12 noon)',
        'afternoon' => 'Afternoon (12 – 4 pm)',
        'evening' => 'Evening (after 4 pm)',
    ];

    protected $fillable = [
        'service', 'department', 'doctor_id', 'status', 'first_name', 'last_name', 'date_of_birth', 'phone', 'email',
        'returning_patient', 'preferred_date', 'preferred_window', 'alternate_date', 'reason', 'has_referral',
        'scheduled_for', 'message_to_patient', 'staff_notes', 'handled_by', 'status_changed_at',
    ];

    #[\Override]
    protected function casts(): array
    {
        return [
            'service' => AppointmentService::class,
            'status' => AppointmentStatus::class,
            'date_of_birth' => 'date',
            'preferred_date' => 'date',
            'alternate_date' => 'date',
            'scheduled_for' => 'datetime',
            'returning_patient' => 'boolean',
            'has_referral' => 'boolean',
            'status_changed_at' => 'datetime',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        static::saving(function (self $a): void {
            $a->phone_digits = preg_replace('/\D+/', '', (string) $a->phone) ?: null;
        });
    }

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /** @return BelongsTo<User, $this> */
    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function windowLabel(): string
    {
        return self::WINDOWS[$this->preferred_window] ?? $this->preferred_window;
    }

    /** "Radiology & ultrasound · Cardiology" */
    public function serviceLabel(): string
    {
        $dept = department($this->department)['name'] ?? null;

        return $this->service->label().($dept ? ' · '.$dept : '');
    }

    /** @param Builder<self> $query */
    public function scopeAwaiting(Builder $query): void
    {
        $query->where('status', AppointmentStatus::Requested);
    }

    /**
     * @param  Builder<self>  $query
     * @param  array<string, string|null>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['status'] ?? null, fn (Builder $q, $v) => $q->where('status', $v))
            ->when($filters['service'] ?? null, fn (Builder $q, $v) => $q->where('service', $v))
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
