<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One weekly slot in the specialist clinic timetable.
 *
 * @property int $id
 * @property string $department
 * @property int|null $doctor_id
 * @property string|null $label
 * @property int $weekday
 * @property string $starts_at
 * @property string $ends_at
 * @property string|null $note
 * @property bool $is_active
 * @property-read Doctor|null $doctor
 */
class ClinicSession extends Model
{
    public const array WEEKDAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    protected $fillable = ['department', 'doctor_id', 'label', 'weekday', 'starts_at', 'ends_at', 'note', 'is_active'];

    #[\Override]
    protected function casts(): array
    {
        return ['weekday' => 'integer', 'is_active' => 'boolean'];
    }

    /** @return BelongsTo<Doctor, $this> */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function dayName(): string
    {
        return self::WEEKDAYS[$this->weekday] ?? '';
    }

    /** "8:00 am – 12:30 pm" */
    public function timeRange(): string
    {
        $fmt = fn (string $t) => strtolower(Carbon::createFromFormat('H:i', substr($t, 0, 5))?->format('g:i a') ?? $t);

        return $fmt($this->starts_at).' – '.$fmt($this->ends_at);
    }

    /** Who runs it: the doctor (only when published) or the free-text label. */
    public function who(): ?string
    {
        return $this->doctor !== null && $this->doctor->is_published ? $this->doctor->name : $this->label;
    }
}
