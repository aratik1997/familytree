<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">{{ __('Super Admin') }}</p>
        <h2 class="text-2xl mt-1">{{ __('Add admin') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.admins.store') }}" class="card p-6 space-y-5">
                @csrf

                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('They will get an email with a link to set their own password. Their tree starts empty — the first person in it is theirs to add.') }}
                </p>

                <div>
                    <x-input-label for="name" :value="__('Full name')" />
                    <x-text-input id="name" name="name" type="text" class="block mt-1 w-full"
                                  :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" name="email" type="email" class="block mt-1 w-full"
                                  :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="tree_name" :value="__('Name of their tree (optional)')" />
                    <x-text-input id="tree_name" name="tree_name" type="text" class="block mt-1 w-full"
                                  :value="old('tree_name')" />
                    <p class="text-xs mt-1" style="color: var(--text-low)">
                        {{ __('Left blank, it is named after them.') }}
                    </p>
                    <x-input-error :messages="$errors->get('tree_name')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Add admin') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
