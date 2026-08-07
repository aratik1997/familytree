<?php

namespace App\Models;

use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Person extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'mobile',
        'address',
        'social_links',
        'profile_photo_path',
        'date_of_birth',
        'gender',
        'bio',
        'is_deceased',
        'death_date',
        'claim_status',
        'invited_at',
        'claimed_at',
        'created_by_person_id',
        'tree_pos_x',
        'tree_pos_y',
    ];

    protected function casts(): array
    {
        return [
            'social_links' => 'array',
            'date_of_birth' => 'date',
            'death_date' => 'date',
            'is_deceased' => 'boolean',
            'invited_at' => 'datetime',
            'claimed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'created_by_person_id');
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_parent', 'child_id', 'parent_id')
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'person_parent', 'parent_id', 'child_id')
            ->withPivot('relationship_type')
            ->withTimestamps();
    }

    /**
     * Where this person's picture is actually served from — a Cloudinary
     * address for anything uploaded since the switch, a path on the public
     * disk for the ones stored before it.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        return ImageStore::url($this->profile_photo_path);
    }

    public function marriagesAsA(): HasMany
    {
        return $this->hasMany(Couple::class, 'person_a_id');
    }

    public function marriagesAsB(): HasMany
    {
        return $this->hasMany(Couple::class, 'person_b_id');
    }

    /**
     * All spouses/partners (past and present), from either side of the couples table.
     */
    public function spouses(): Collection
    {
        return $this->marriagesAsA->pluck('personB')
            ->merge($this->marriagesAsB->pluck('personA'))
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * People sharing at least one parent with this person, excluding self.
     */
    public function siblings(): Collection
    {
        return $this->parents
            ->flatMap(fn (Person $parent) => $parent->children)
            ->filter(fn (Person $person) => $person->id !== $this->id)
            ->unique('id')
            ->values();
    }

    public function profileFieldPrivacy(): HasMany
    {
        return $this->hasMany(ProfileFieldPrivacy::class);
    }

    public function customFields(): HasMany
    {
        return $this->hasMany(ProfileField::class)->orderBy('sort_order');
    }

    public function invites(): HasMany
    {
        return $this->hasMany(ClaimInvite::class);
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function isMinor(): bool
    {
        return $this->date_of_birth->diffInYears(now()) < 18;
    }

    public function isClaimed(): bool
    {
        return $this->user_id !== null;
    }

    /**
     * True if $other appears anywhere in this person's descendant tree.
     * Used to block a drag-and-drop reparent (or "Add Parent") that would
     * otherwise make someone their own ancestor.
     */
    public function isAncestorOf(Person $other): bool
    {
        $frontier = $this->children()->pluck('people.id')->all();
        $visited = [];

        // Walked a generation at a time: one query per level rather than one
        // per person. `people.id` has to be qualified on both sides — the
        // join brings person_parent's own columns into scope, so a bare `id`
        // is ambiguous to MySQL.
        while ($frontier) {
            $frontier = array_values(array_diff(array_unique($frontier), $visited));

            if ($frontier === []) {
                break;
            }

            if (in_array($other->id, $frontier)) {
                return true;
            }

            $visited = array_merge($visited, $frontier);

            $frontier = static::query()
                ->join('person_parent', 'people.id', '=', 'person_parent.child_id')
                ->whereIn('person_parent.parent_id', $frontier)
                ->pluck('people.id')
                ->all();
        }

        return false;
    }

    /**
     * Family = parent/child/sibling/spouse, or a grandparent/grandchild/
     * sibling's-spouse (one hop further out through those same relations).
     */
    public function isFamilyOf(Person $other): bool
    {
        if ($this->id === $other->id) {
            return true;
        }

        $oneHop = $this->closeRelations();

        if ($oneHop->contains('id', $other->id)) {
            return true;
        }

        return $oneHop
            ->flatMap(fn (Person $person) => $person->closeRelations())
            ->contains('id', $other->id);
    }

    /**
     * Parents, children, siblings, and spouses — the "one hop" family circle.
     */
    protected function closeRelations(): Collection
    {
        return $this->parents
            ->merge($this->children)
            ->merge($this->siblings())
            ->merge($this->spouses())
            ->unique('id')
            ->values();
    }
}
