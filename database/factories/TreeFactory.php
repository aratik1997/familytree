<?php

namespace Database\Factories;

use App\Models\Tree;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tree>
 */
class TreeFactory extends Factory
{
    protected $model = Tree::class;

    public function definition(): array
    {
        return [
            'name' => fake()->lastName().' family',
        ];
    }
}
