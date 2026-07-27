<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl">
            {{ __('Add Parent of') }} {{ $child->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.people.parents.store', $child) }}" enctype="multipart/form-data"
                  class="card p-6 space-y-5" x-data="{ mode: '{{ old('mode', 'existing') }}' }">
                @csrf

                <div>
                    <x-input-label :value="__('Parent')" />
                    <div class="flex gap-4 mt-1 text-sm">
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="mode" value="existing" x-model="mode" class="accent-gold">
                            {{ __('Someone already in the tree') }}
                        </label>
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="mode" value="new" x-model="mode" class="accent-gold">
                            {{ __('A new person') }}
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('mode')" class="mt-1" />
                </div>

                <div x-show="mode === 'existing'">
                    <x-input-label for="existing_person_id" :value="__('Person')" />
                    <select id="existing_person_id" name="existing_person_id" class="field mt-1">
                        <option value="">{{ __('Select…') }}</option>
                        @if ($existingCouples->isNotEmpty())
                            <optgroup label="{{ __('Couples') }}">
                                @foreach ($existingCouples as $couple)
                                    @php $value = "couple:{$couple->person_a_id}-{$couple->person_b_id}"; @endphp
                                    <option value="{{ $value }}" @selected(old('existing_person_id') === $value)>
                                        {{ __(':a and :b', ['a' => $couple->personA->full_name, 'b' => $couple->personB->full_name]) }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="{{ __('Individual people') }}">
                            @foreach ($existingCandidates as $candidate)
                                <option value="{{ $candidate->id }}" @selected(old('existing_person_id') == $candidate->id)>{{ $candidate->full_name }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                    <x-input-error :messages="$errors->get('existing_person_id')" class="mt-1" />
                </div>

                <div x-show="mode === 'new'" class="space-y-5">
                    <div>
                        <x-input-label for="full_name" :value="__('Full name')" />
                        <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" :value="old('full_name')" />
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
                    <x-input-label for="relationship_type" :value="__('Relationship to '.$child->full_name)" />
                    <select id="relationship_type" name="relationship_type" class="field mt-1">
                        <option value="biological">{{ __('Biological') }}</option>
                        <option value="step">{{ __('Step') }}</option>
                        <option value="adoptive">{{ __('Adoptive') }}</option>
                        <option value="guardian">{{ __('Guardian') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('relationship_type')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('people.show', $child) }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Add parent') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
