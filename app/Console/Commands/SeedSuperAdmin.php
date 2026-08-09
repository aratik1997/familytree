<?php

namespace App\Console\Commands;

use App\Models\Person;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SeedSuperAdmin extends Command
{
    protected $signature = 'app:seed-super-admin
        {--photo= : Path to a profile photo file on disk}
        {--name= : Full name (skips the prompt)}
        {--email= : Email address (skips the prompt)}
        {--password= : Password (skips the prompt)}
        {--dob= : Date of birth, YYYY-MM-DD (skips the prompt)}';

    protected $description = 'Create the very first tree member and the Super Admin login for The Khandani Legacy';

    public function handle(): int
    {
        if (User::query()->where('is_super_admin', true)->exists()) {
            $this->error('A Super Admin already exists.');

            return self::FAILURE;
        }

        $fullName = $this->option('name') ?: $this->ask('Full name');
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password') ?: $this->secret('Password');
        $dateOfBirth = $this->option('dob') ?: $this->ask('Date of birth (YYYY-MM-DD)');
        $photoPath = $this->option('photo') ?: $this->ask('Path to a profile photo file on disk');

        $validator = Validator::make(
            compact('fullName', 'email', 'password', 'dateOfBirth'),
            [
                'fullName' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email'],
                'password' => ['required', 'string', 'min:8'],
                'dateOfBirth' => ['required', 'date', 'before:today'],
            ]
        );

        if (! $photoPath || ! is_file($photoPath) || getimagesize($photoPath) === false) {
            $validator->errors()->add('photoPath', 'The profile photo path must point to a real image file.');
        }

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $storedPhotoPath = $this->storePhoto($photoPath);

        $person = Person::create([
            'full_name' => $fullName,
            'email' => $email,
            'profile_photo_path' => $storedPhotoPath,
            'date_of_birth' => $dateOfBirth,
        ]);

        $user = User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
        ]);

        $person->update(['user_id' => $user->id, 'claim_status' => 'claimed', 'claimed_at' => now()]);

        $this->info("Super Admin created. You can now log in as {$email}.");

        return self::SUCCESS;
    }

    private function storePhoto(string $sourcePath): string
    {
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'jpg';
        $destination = 'profile-photos/'.Str::uuid().'.'.$extension;

        Storage::disk('public')->put($destination, file_get_contents($sourcePath));

        return $destination;
    }
}
