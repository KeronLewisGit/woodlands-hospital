<?php

namespace App\Models;

use App\Enums\RefillStatus;
use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $reference
 * @property RefillStatus $status
 * @property string $name
 * @property Carbon|null $date_of_birth
 * @property string $phone
 * @property string|null $email
 * @property string $medications
 * @property string|null $prescriber
 * @property string|null $notes
 * @property string|null $staff_notes
 * @property Carbon $created_at
 */
class RefillRequest extends Model
{
    use HasReference;

    public const string REFERENCE_PREFIX = 'RX';

    protected $fillable = ['status', 'name', 'date_of_birth', 'phone', 'email', 'medications', 'prescriber', 'notes', 'staff_notes'];

    #[\Override]
    protected function casts(): array
    {
        return ['status' => RefillStatus::class, 'date_of_birth' => 'date'];
    }
}
