<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl">
            {{ __('Reset Password') }} — {{ $person->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-lg mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.people.password.update', $person) }}" class="card p-6 space-y-5">
                @csrf
                @method('PUT')

                <p class="text-sm text-ink/60">
                    {{ __('This sets a new login password for :name. They will need to use it the next time they sign in.', ['name' => $person->full_name]) }}
                </p>

                <div>
                    <x-input-label for="password" :value="__('New Password')" />
                    <x-text-input id="password" type="password" name="password" class="block mt-1 w-full" required autofocus />
                    <x-input-error :messages="$errors->get('password')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
                    <x-text-input id="password_confirmation" type="password" name="password_confirmation" class="block mt-1 w-full" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Update password') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
