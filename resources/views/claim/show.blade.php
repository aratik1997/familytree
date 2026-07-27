<x-guest-layout>
    <h2 class="font-serif text-2xl text-content-hi mb-1">{{ __('Claim Your Account') }}</h2>
    <p class="text-sm text-ink/60 mb-6">
        {{ __('Welcome,') }} {{ $person->full_name }}. {{ __('Set a password to take over managing your own profile.') }}
    </p>

    <form method="POST" action="{{ route('claim.store', $token) }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Login Email')" />
            <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $person->email)" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" class="block mt-1 w-full" required />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full" required />
        </div>

        <div class="flex justify-end pt-2">
            <x-primary-button>{{ __('Claim Account') }}</x-primary-button>
        </div>
    </form>
</x-guest-layout>
