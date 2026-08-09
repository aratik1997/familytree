<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl">
            {{ __('Add Person') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('admin.people.store') }}" enctype="multipart/form-data"
                  class="card p-6 space-y-5" x-data="{ hasChildOf: '{{ old('child_person_id') ? 'true' : 'false' }}' === 'true' }">
                @csrf

                <div>
                    <x-input-label for="full_name" :value="__('Full name')" />
                    <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" :value="old('full_name')" required autofocus />
                    <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email')" required />
                    <x-input-error :messages="$errors->get('email')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                    <x-text-input id="date_of_birth" type="date" name="date_of_birth" class="block mt-1 w-full" :value="old('date_of_birth')" required />
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

                <div class="border-t border-gold-light/30 pt-5 space-y-5">
                    <div>
                        <x-input-label for="parent_selection" :value="__('Child of')" />
                        <select id="parent_selection" name="parent_selection" required
                                class="field mt-1">
                            <option value="">{{ __('Select…') }}</option>
                            @if ($existingCouples->isNotEmpty())
                                <optgroup label="{{ __('Couples') }}">
                                    @foreach ($existingCouples as $couple)
                                        @php $value = "couple:{$couple->person_a_id}-{$couple->person_b_id}"; @endphp
                                        <option value="{{ $value }}" @selected(old('parent_selection') === $value)>
                                            {{ __(':a and :b', ['a' => $couple->personA->full_name, 'b' => $couple->personB->full_name]) }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="{{ __('Individual people') }}">
                                @foreach ($existingCandidates as $candidate)
                                    <option value="{{ $candidate->id }}" @selected(old('parent_selection') == $candidate->id)>{{ $candidate->full_name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <x-input-error :messages="$errors->get('parent_selection')" class="mt-1" />
                    </div>

                    <div>
                        <x-input-label for="parent_relationship_type" :value="__('Relationship')" />
                        <select id="parent_relationship_type" name="parent_relationship_type" class="field mt-1">
                            <option value="biological">{{ __('Biological') }}</option>
                            <option value="step">{{ __('Step') }}</option>
                            <option value="adoptive">{{ __('Adoptive') }}</option>
                            <option value="guardian">{{ __('Guardian') }}</option>
                        </select>
                        <x-input-error :messages="$errors->get('parent_relationship_type')" class="mt-1" />
                    </div>
                </div>

                <div class="border-t border-gold-light/30 pt-5 space-y-5">
                    <div>
                        <label class="inline-flex items-center gap-2 text-sm text-ink">
                            <input type="checkbox" x-model="hasChildOf" class="checkbox">
                            {{ __('Also make them the parent of someone already in the tree (optional)') }}
                        </label>
                    </div>

                    <div x-show="hasChildOf" class="space-y-5">
                        <div>
                            <x-input-label for="child_person_id" :value="__('Parent of')" />
                            <select id="child_person_id" name="child_person_id" class="field mt-1">
                                <option value="">{{ __('Select…') }}</option>
                                @foreach ($existingCandidates as $candidate)
                                    <option value="{{ $candidate->id }}" @selected(old('child_person_id') == $candidate->id)>{{ $candidate->full_name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('child_person_id')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="child_relationship_type" :value="__('Relationship')" />
                            <select id="child_relationship_type" name="child_relationship_type" class="field mt-1">
                                <option value="biological">{{ __('Biological') }}</option>
                                <option value="step">{{ __('Step') }}</option>
                                <option value="adoptive">{{ __('Adoptive') }}</option>
                                <option value="guardian">{{ __('Guardian') }}</option>
                            </select>
                            <x-input-error :messages="$errors->get('child_relationship_type')" class="mt-1" />
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Add Person') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
