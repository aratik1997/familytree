<?php

namespace Database\Factories;

use App\Models\Person;
use App\Models\Tree;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Auth;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    public function definition(): array
    {
        return [
            // The signed-in person's tree where there is one, so a test that
            // acts as somebody first gets a person they can actually reach.
            // Otherwise a tree of its own, which is what an isolation test
            // wants: a person belonging to a family the viewer is not in.
            'tree_id' => Auth::user()?->tree_id ?? Tree::factory(),
            'user_id' => null,
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'date_of_birth' => fake()->dateTimeBetween('-70 years', '-19 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'is_deceased' => false,
            'claim_status' => 'not_applicable_minor',
        ];
    }

    /**
     * Under 18, so no account may be claimed for them yet.
     */
    public function minor(): static
    {
        return $this->state(fn () => [
            'date_of_birth' => now()->subYears(10)->format('Y-m-d'),
        ]);
    }

    /**
     * Already has an account, which makes any outstanding invite void.
     */
    public function claimed(): static
    {
        return $this->state(fn () => [
            'user_id' => \App\Models\User::factory(),
            'claim_status' => 'claimed',
            'claimed_at' => now(),
        ]);
    }
}
