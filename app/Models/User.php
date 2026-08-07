<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
        'is_admin',
        'tree_id',
    ];

    /**
     * The tree Person record this login belongs to.
     */
    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    /**
     * The family this login works in. Held here rather than reached through
     * their Person record, because an Admin whose tree is still empty has no
     * Person of their own yet.
     */
    public function tree(): BelongsTo
    {
        return $this->belongsTo(Tree::class);
    }

    /**
     * Whether this login has run of the family records — adding people,
     * editing anyone's profile, changing who is related to whom.
     *
     * An Admin has exactly the powers a Super Admin has, only ever within
     * their own tree. What the Super Admin keeps to themselves is not a bigger
     * reach into the records but a different job: saying who the Admins are.
     */
    public function managesTree(): bool
    {
        return $this->is_super_admin || $this->is_admin;
    }

    /** True when the record belongs to the same family as this login. */
    public function sharesTreeWith(?Model $record): bool
    {
        return $record !== null
            && $this->tree_id !== null
            && $this->tree_id === $record->tree_id;
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
            'is_admin' => 'boolean',
        ];
    }
}
