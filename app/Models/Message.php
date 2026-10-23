<?php

namespace App\Models;

use App\Enums\MessageTopic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Contact-form message.
 *
 * @property int $id
 * @property MessageTopic $topic
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string $body
 * @property Carbon|null $handled_at
 * @property Carbon $created_at
 */
class Message extends Model
{
    protected $fillable = ['topic', 'name', 'email', 'phone', 'body', 'handled_at'];

    #[\Override]
    protected function casts(): array
    {
        return ['topic' => MessageTopic::class, 'handled_at' => 'datetime'];
    }

    /** @param Builder<self> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('handled_at');
    }
}
