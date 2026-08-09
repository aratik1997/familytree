<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_super_admin',
        'is_moderator',
    ];

    /**
     * The tree Person record this login belongs to.
     */
    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    /**
     * Whether this login looks after the family records — adding people,
     * editing anyone's profile, changing who is related to whom.
     *
     * A moderator has exactly the run of the tree the Super Admin has, so
     * keeping it up to date can be shared out. What the Super Admin keeps to
     * themselves is not a longer reach into the records but a different job:
     * saying who the moderators are.
     */
    public function canManageTree(): bool
    {
        return $this->is_super_admin || $this->is_moderator;
    }

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
            'is_super_admin' => 'boolean',
            'is_moderator' => 'boolean',
        ];
    }
}
