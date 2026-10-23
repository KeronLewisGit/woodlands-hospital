<?php

namespace App\Models;

use App\Enums\ResultKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A PDF report on the private disk. Streamed only to its own patient (portal) or to
 * lab/admin staff; there is no public URL.
 *
 * @property int $id
 * @property int $patient_id
 * @property ResultKind $kind
 * @property string $title
 * @property Carbon|null $collected_on
 * @property string $file_path
 * @property string $original_name
 * @property int $size
 * @property int|null $uploaded_by
 * @property Carbon|null $notified_at
 * @property Carbon|null $first_viewed_at
 * @property Carbon $created_at
 * @property-read Patient $patient
 * @property-read User|null $uploader
 */
class PatientResult extends Model
{
    public const string DISK = 'local';

    public const string DIRECTORY = 'results';

    protected $fillable = ['patient_id', 'kind', 'title', 'collected_on', 'file_path', 'original_name', 'size', 'uploaded_by'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'kind' => ResultKind::class,
            'collected_on' => 'date',
            'size' => 'integer',
            'notified_at' => 'datetime',
            'first_viewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Patient, $this> */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function sizeLabel(): string
    {
        return $this->size >= 1_048_576 ? round($this->size / 1_048_576, 1).' MB' : max(1, (int) round($this->size / 1024)).' KB';
    }

    /** File name for the download: "woodlands-lab-2026-10-23-full-blood-count.pdf" */
    public function downloadName(): string
    {
        return 'woodlands-'.$this->kind->value.'-'.$this->created_at->format('Y-m-d').'-'.(Str::slug($this->title) ?: 'result').'.pdf';
    }
}
