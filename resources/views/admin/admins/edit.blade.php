<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">{{ __('Super Admin') }}</p>
        <h2 class="text-2xl mt-1">{{ $admin->name }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.admins.update', $admin) }}" class="card p-6 space-y-5">
                @csrf
                @method('PATCH')

                <div>
                    <x-input-label for="name" :value="__('Full name')" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                                  :value="old('name', $admin->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                                  :value="old('email', $admin->email)" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tree_name" :value="__('Name of their tree')" />
                    <x-text-input id="tree_name" name="tree_name" type="text" class="block mt-1 w-full"
                                  :value="old('tree_name', $admin->tree?->name)" required />
                    <x-input-error :messages="$errors->get('tree_name')" class="mt-2" />
                </div>

                <p class="text-sm hairline p-3" style="border-radius: var(--radius-control); color: var(--text-mid)">
                    {{ __('You cannot open their tree or read the people in it. To let them back in, send the invitation again — it lets them set a new password.') }}
                </p>

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
