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
     * The tree this login is looking at right now.
     *
     * Their own by default. Somebody who has been accepted into another
     * family's tree can switch to it, and that choice lives in the session —
     * it is a view, not a change to who they are.
     */
    public function currentTreeId(): ?int
    {
        $chosen = session('current_tree_id');

        if ($chosen !== null && in_array((int) $chosen, $this->accessibleTreeIds(), true)) {
            return (int) $chosen;
        }

        return $this->tree_id;
    }

    /** Their own tree, plus any that have accepted them. */
    public function accessibleTreeIds(): array
    {
        $own = $this->tree_id === null ? [] : [$this->tree_id];

        // Read without the global scope: working out which trees are reachable
        // cannot itself be confined to the tree already reachable.
        $shared = $this->person === null
            ? []
            : TreeMembership::query()
                ->where('person_id', $this->person->id)
                ->where('status', 'accepted')
                ->pluck('tree_id')
                ->all();

        return array_values(array_unique([...$own, ...array_map('intval', $shared)]));
    }

    public function canSeeTree(int $treeId): bool
    {
        return in_array($treeId, $this->accessibleTreeIds(), true);
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

    /**
     * True when the record is on show in the tree currently being viewed —
     * either because it was entered there, or because that tree has accepted
     * the person into it.
     */
    public function sharesTreeWith(?Model $record): bool
    {
        $treeId = $this->currentTreeId();

        if ($record === null || $treeId === null) {
            return false;
        }

        if ((int) $record->tree_id === $treeId) {
            return true;
        }

        return $record instanceof Person
            && $record->acceptedMemberships()->where('tree_id', $treeId)->exists();
    }

    /**
     * True only for records entered in the tree being viewed — a guest lent
     * from another family does not count.
     *
     * The difference from sharesTreeWith is what stops one family editing
     * another's people. A father-in-law can place his son-in-law in his tree
     * and record how they are related, but the son-in-law's name, photo and
     * privacy settings stay his own and his own family's business.
     */
    public function ownsRecordTree(?Model $record): bool
    {
        return $record !== null
            && $this->currentTreeId() !== null
            && (int) $record->tree_id === $this->currentTreeId();
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
