<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens, HasRoles;

    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        // Written by SocialAuthController on every Google login. Missing here,
        // update()/create() silently dropped them (Eloquent mass-assignment just
        // skips an unlisted key rather than erroring) — no user in production has
        // ever actually had provider/provider_id persisted, so the "already linked"
        // branch of that lookup has been dead code since it shipped; every social
        // login has been falling through to matching by email alone.
        'provider',
        'provider_id',
        'avatar',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }


    public function orders() : HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'sender_id');
    }

    public function receivedMessages()
    {
        return $this->hasMany(\App\Models\Message::class, 'receiver_id');
    }

    public function messages()
    {
        return $this->sentMessages->merge($this->receivedMessages);
    }

    public function vendor(): HasOne
    {
        return $this->hasOne(Vendor::class);
    }

    public function following()
    {
        return $this->belongsToMany(Vendor::class, 'vendor_follows', 'user_id', 'vendor_id')
                    ->withTimestamps();
    }

    // app/Models/User.php

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'vendor' => $this->hasRole('vendor') && $this->vendor()->exists(),
            default => $this->hasAnyRole(['admin', 'manager']),
        };
    }

}
