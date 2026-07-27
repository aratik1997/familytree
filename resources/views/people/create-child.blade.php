<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl">
            {{ __('Add a child of') }} {{ $parent->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('people.children.store', $parent) }}" enctype="multipart/form-data"
                  class="card p-6 space-y-5" x-data="{ mode: '{{ old('mode', 'new') }}' }">
                @csrf

                @if (auth()->user()->is_super_admin)
                    <div>
                        <x-input-label :value="__('Child')" />
                        <div class="flex gap-4 mt-1 text-sm">
                            <label class="inline-flex items-center gap-1.5">
                                <input type="radio" name="mode" value="new" x-model="mode" class="accent-gold">
                                {{ __('A new person') }}
                            </label>
                            <label class="inline-flex items-center gap-1.5">
                                <input type="radio" name="mode" value="existing" x-model="mode" class="accent-gold">
                                {{ __('Someone already in the tree') }}
                            </label>
                        </div>
                        <x-input-error :messages="$errors->get('mode')" class="mt-1" />
                    </div>

                    <div x-show="mode === 'existing'">
                        <x-input-label for="existing_person_id" :value="__('Person')" />
                        <select id="existing_person_id" name="existing_person_id" class="field mt-1">
                            <option value="">{{ __('Select…') }}</option>
                            @foreach ($existingCandidates as $candidate)
                                <option value="{{ $candidate->id }}" @selected(old('existing_person_id') == $candidate->id)>{{ $candidate->full_name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('existing_person_id')" class="mt-1" />
                    </div>
                @else
                    <input type="hidden" name="mode" value="new">
                @endif

                <div x-show="mode !== 'existing'" class="space-y-5">
                    <div>
                        <x-input-label for="full_name" :value="__('Full name')" />
                        <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" :value="old('full_name')" autofocus />
                        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                        <x-text-input id="date_of_birth" type="date" name="date_of_birth" class="block mt-1 w-full" :value="old('date_of_birth')" />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="gender" :value="__('Gender')" />
                        <x-gender-select :selected="old('gender')" />
                        <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label :value="__('Profile photo')" />
                        <x-image-upload name="photo" />
                        <x-input-error :messages="$errors->get('photo')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="relationship_type" :value="__('Relationship to '.$parent->full_name)" />
                    <select id="relationship_type" name="relationship_type" class="field mt-1">
                        <option value="biological">{{ __('Biological') }}</option>
                        <option value="step">{{ __('Step') }}</option>
                        <option value="adoptive">{{ __('Adoptive') }}</option>
                        <option value="guardian">{{ __('Guardian') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('relationship_type')" class="mt-1" />
                </div>

                @if ($possibleCoParents->isNotEmpty())
                    <div>
                        <x-input-label for="co_parent_id">
                            {{ $otherParentLabel }}
                            @unless ($otherParentRequired)
                                <span class="text-ink/50 font-normal">{{ __('(optional)') }}</span>
                            @endunless
                        </x-input-label>

                        @if ($otherParentRequired)
                            <p class="mt-1 text-xs text-ink/60">
                                {{ __(':name has more than one spouse — please choose which one this child is from.', ['name' => $parent->full_name]) }}
                            </p>
                        @endif

                        <select id="co_parent_id" name="co_parent_id" class="field mt-1">
                            <option value="">{{ $otherParentRequired ? __('Select…') : __('None') }}</option>
                            @foreach ($possibleCoParents as $marriage)
                                <option value="{{ $marriage['person']->id }}" @selected(old('co_parent_id') == $marriage['person']->id)>
                                    {{ $marriage['person']->full_name }}@if ($marriage['status'] !== 'married') ({{ __(ucfirst($marriage['status'])) }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('co_parent_id')" class="mt-1" />
                    </div>
                @endif

                <div class="flex justify-end pt-2">
                    <x-primary-button>{{ __('Add child') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
