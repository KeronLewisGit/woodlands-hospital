<?php

namespace App\Models;

use App\Notifications\PatientResetPassword;
use App\Notifications\PortalInvitation;
use App\Support\Notify;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;

/**
 * Results-portal account (guard "patient"). Created by staff only; the patient sets
 * a password from an emailed, signed invitation. Never exposed by a public sign-up form.
 *
 * @property int $id
 * @property string|null $hospital_number
 * @property string $first_name
 * @property string $last_name
 * @property Carbon|null $date_of_birth
 * @property string $email
 * @property string|null $phone
 * @property string|null $password
 * @property Carbon|null $invited_at
 * @property Carbon|null $activated_at
 * @property Carbon|null $last_login_at
 * @property-read string $full_name
 */
class Patient extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['hospital_number', 'first_name', 'last_name', 'date_of_birth', 'email', 'phone', 'created_by'];

    protected $hidden = ['password', 'remember_token'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'date_of_birth' => 'date',
            'invited_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /** @return HasMany<PatientResult, $this> */
    public function results(): HasMany
    {
        return $this->hasMany(PatientResult::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isActivated(): bool
    {
        return $this->password !== null && $this->activated_at !== null;
    }

    /** Emails a fresh portal invitation. The new invited_at is part of the link, so older links stop working. */
    public function invite(bool $resultsWaiting = false): void
    {
        $this->forceFill(['invited_at' => now()])->save();
        Notify::person($this, new PortalInvitation($resultsWaiting));
    }

    /** An account without a password can never sign in (Auth::attempt fails on a null hash). */
    #[\Override]
    public function getAuthPassword(): string
    {
        return (string) $this->password;
    }

    #[\Override]
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new PatientResetPassword($token));
    }

    /** @param Builder<self> $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if (! $term) {
            return;
        }
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $query->where(fn (Builder $w) => $w->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
            ->orWhere('email', 'like', $like)->orWhere('hospital_number', 'like', $like));
    }
}
