<?php

namespace App\Http\Resources;

use App\Support\FieldVisibility;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * A privacy-filtered shape for the D3/Three.js tree views. This must never
 * leak a field the viewer isn't allowed to see — the JSON is the actual
 * network payload, not just something the UI chooses not to render.
 */
class PersonNodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $canSeeName = FieldVisibility::canSee($viewer, $this->resource, FieldVisibility::visibilityFor($this->resource, 'full_name'));
        $canSeePhoto = FieldVisibility::canSee($viewer, $this->resource, FieldVisibility::visibilityFor($this->resource, 'profile_photo_path'));

        return [
            'id' => $this->id,
            'name' => $canSeeName ? $this->full_name : 'Private',
            'photo_url' => ($canSeePhoto && $this->profile_photo_path) ? Storage::disk('public')->url($this->profile_photo_path) : null,
            'birth_year' => $this->date_of_birth?->year,
            'birth_date' => $this->date_of_birth?->format('Y-m-d'),
            // Drives the "1958 – 2019" lifespan on the tree card.
            'death_year' => $this->death_date?->year,
            'age' => $this->date_of_birth?->age,
            'is_minor' => $this->isMinor(),
            'is_claimed' => $this->isClaimed(),
            'gender' => $this->gender,
            'is_deceased' => $this->is_deceased,
            'pos_x' => $this->tree_pos_x,
            'pos_y' => $this->tree_pos_y,
        ];
    }
}
