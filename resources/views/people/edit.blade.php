@use('App\Support\FieldVisibility')

@php
$privacyFor = fn (string $field) => FieldVisibility::visibilityFor($person, $field);
$social = $person->social_links ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl">
            {{ __('Edit profile') }} — {{ $person->full_name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('profile-updated') {{ __('Profile saved.') }} @break
                        @case('photo-updated') {{ __('Photo saved.') }} @break
                        @case('marriage-updated') {{ __('Marriage updated.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            {{-- Profile photo --}}
            <div class="card p-6">
                <h3 class="font-serif text-xl text-content-hi mb-4">{{ __('Profile photo') }}</h3>
                <form method="POST" action="{{ route('people.photo.update', $person) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @method('POST')
                    <x-image-upload name="photo" :current-url="$person->photo_url" />
                    <x-input-error :messages="$errors->get('photo')" />
                    <x-primary-button>{{ __('Upload Photo') }}</x-primary-button>
                </form>
            </div>

            {{-- Baseline fields --}}
            <form method="POST" action="{{ route('people.update', $person) }}" class="card p-6 space-y-5">
                @csrf
                @method('PATCH')

                <div class="flex items-center justify-between border-b border-gold-light/30 pb-4 mb-1">
                    <h3 class="font-serif text-xl text-content-hi">{{ __('Details') }}</h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="full_name" :value="__('Full name')" />
                        <x-text-input id="full_name" name="full_name" class="block mt-1 w-full" :value="old('full_name', $person->full_name)" required />
                        <x-input-error :messages="$errors->get('full_name')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[full_name]" :selected="$privacyFor('full_name')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="email" :value="__('Email')" />
                        <x-text-input id="email" type="email" name="email" class="block mt-1 w-full" :value="old('email', $person->email)" required />
                        <x-input-error :messages="$errors->get('email')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[email]" :selected="$privacyFor('email')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="mobile" :value="__('Mobile')" />
                        <x-text-input id="mobile" name="mobile" class="block mt-1 w-full" :value="old('mobile', $person->mobile)" />
                        <x-input-error :messages="$errors->get('mobile')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[mobile]" :selected="$privacyFor('mobile')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="address" :value="__('Address')" />
                        <x-text-input id="address" name="address" class="block mt-1 w-full" :value="old('address', $person->address)" />
                        <x-input-error :messages="$errors->get('address')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[address]" :selected="$privacyFor('address')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label for="social_facebook" :value="__('Facebook')" />
                            <x-text-input id="social_facebook" name="social_links[facebook]" class="block mt-1 w-full" :value="old('social_links.facebook', $social['facebook'] ?? '')" />
                        </div>
                        <div>
                            <x-input-label for="social_instagram" :value="__('Instagram')" />
                            <x-text-input id="social_instagram" name="social_links[instagram]" class="block mt-1 w-full" :value="old('social_links.instagram', $social['instagram'] ?? '')" />
                        </div>
                    </div>
                    <x-privacy-select name="field_privacy[social_links]" :selected="$privacyFor('social_links')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="bio" :value="__('Bio')" />
                        <textarea id="bio" name="bio" rows="3" class="field mt-1">{{ old('bio', $person->bio) }}</textarea>
                        <x-input-error :messages="$errors->get('bio')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[bio]" :selected="$privacyFor('bio')" class="mt-6" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-3 items-start">
                    <div>
                        <x-input-label for="date_of_birth" :value="__('Date of birth')" />
                        <x-text-input id="date_of_birth" type="date" name="date_of_birth" class="block mt-1 w-full" :value="old('date_of_birth', $person->date_of_birth->format('Y-m-d'))" required />
                        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-1" />
                    </div>
                    <x-privacy-select name="field_privacy[date_of_birth]" :selected="$privacyFor('date_of_birth')" class="mt-6" />
                </div>

                <div>
                    <x-input-label for="gender" :value="__('Gender')" />
                    <x-gender-select :selected="old('gender', $person->gender)" class="sm:w-64" />
                    <x-input-error :messages="$errors->get('gender')" class="mt-1" />
                </div>

                <div x-data="{ deceased: {{ $person->is_deceased ? 'true' : 'false' }} }" class="space-y-3 pt-2 border-t border-gold-light/20">
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="is_deceased" value="0">
                        <input type="checkbox" name="is_deceased" value="1" x-model="deceased" class="checkbox" @if($person->is_deceased) checked @endif>
                        <span class="text-sm text-ink">{{ __('This person is deceased') }}</span>
                    </label>
                    <div x-show="deceased">
                        <x-input-label for="death_date" :value="__('Date of Death')" />
                        <x-text-input id="death_date" type="date" name="death_date" class="block mt-1 w-full sm:w-64" :value="old('death_date', optional($person->death_date)->format('Y-m-d'))" />
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>
            </form>

            {{-- Family links. Adding one has always been possible from the
                 tree or the "Add" pages; removing one had no way in at all,
                 so a wrong link — most easily made by dropping one card onto
                 another — stayed on the chart for good. Super Admin only:
                 these are the family record itself, not this person's own
                 profile. --}}
            @if (auth()->user()->managesTree())
                <div class="card p-6 space-y-6">
                    <div>
                        <h3 class="font-serif text-xl">{{ __('Family links') }}</h3>
                        <p class="text-sm measure mt-1" style="color: var(--text-mid)">
                            {{ __('Who this person descends from and who descends from them. Removing a link only unpicks the connection — it never deletes anybody.') }}
                        </p>
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium">{{ __('Parents') }}</h4>
                            <a href="{{ route('admin.people.parents.create', $person) }}" class="btn btn-secondary text-xs">
                                {{ __('Add parent') }}
                            </a>
                        </div>

                        @forelse ($person->parents as $parent)
                            <div class="hairline p-3 mt-2 flex flex-wrap items-center justify-between gap-2"
                                 style="border-radius: var(--radius-control)">
                                <p>
                                    {{ $parent->full_name }}
                                    <span class="text-xs" style="color: var(--text-low)">
                                        {{ $parent->pivot->relationship_type }}
                                    </span>
                                </p>
                                <form method="POST"
                                      action="{{ route('admin.relationships.detach', [$person, $parent]) }}"
                                      onsubmit="return confirm('Remove {{ $parent->full_name }} as a parent of {{ $person->full_name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary text-xs">{{ __('Remove') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm mt-2" style="color: var(--text-low)">
                                {{ __('No parents recorded — this person starts a line.') }}
                            </p>
                        @endforelse
                    </div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-medium">{{ __('Children') }}</h4>
                            <a href="{{ route('people.children.create', $person) }}" class="btn btn-secondary text-xs">
                                {{ __('Add child') }}
                            </a>
                        </div>

                        @forelse ($person->children as $child)
                            <div class="hairline p-3 mt-2 flex flex-wrap items-center justify-between gap-2"
                                 style="border-radius: var(--radius-control)">
                                <p>
                                    {{ $child->full_name }}
                                    <span class="text-xs" style="color: var(--text-low)">
                                        {{ $child->pivot->relationship_type }}
                                    </span>
                                </p>
                                {{-- Same link seen from the other end, so the
                                     child is the one it hangs off. --}}
                                <form method="POST"
                                      action="{{ route('admin.relationships.detach', [$child, $person]) }}"
                                      onsubmit="return confirm('Remove {{ $child->full_name }} as a child of {{ $person->full_name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-secondary text-xs">{{ __('Remove') }}</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-sm mt-2" style="color: var(--text-low)">
                                {{ __('No children recorded.') }}
                            </p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Marriages: the same kind of life-status record as "deceased"
                 above, but one that belongs to the couple rather than the
                 person, so each is saved on its own. --}}
            @if ($marriages->isNotEmpty())
                <div class="card p-6 space-y-4">
                    <div>
                        <h3 class="font-serif text-xl">{{ __('Marriages') }}</h3>
                        <p class="text-sm measure mt-1" style="color: var(--text-mid)">
                            {{ __('A divorced or ended marriage still belongs in the record — it is drawn differently on the tree rather than removed.') }}
                        </p>
                    </div>

                    @foreach ($marriages as $marriage)
                        @php($couple = $marriage['couple'])
                        <form method="POST" action="{{ route('couples.update', $couple) }}"
                              class="hairline p-4 space-y-3"
                              style="border-radius: var(--radius-control)"
                              x-data="{ status: '{{ old('status', $couple->status) }}' }">
                            @csrf
                            @method('PATCH')

                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="font-medium">
                                    {{ __('With') }} {{ $marriage['partner']->full_name }}
                                </p>
                                @if ($couple->started_on)
                                    <p class="numeric text-xs" style="color: var(--text-low)">
                                        {{ __('since') }} {{ $couple->started_on->format('Y-m-d') }}
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-4 items-end">
                                <div class="flex-1 min-w-[12rem]">
                                    <x-input-label :for="'status-'.$couple->id" :value="__('Status')" />
                                    <select id="status-{{ $couple->id }}" name="status" x-model="status" class="field mt-1">
                                        <option value="married">{{ __('Married') }}</option>
                                        <option value="divorced">{{ __('Divorced') }}</option>
                                        <option value="separated">{{ __('Separated') }}</option>
                                        <option value="widowed">{{ __('Widowed') }}</option>
                                        <option value="partnered">{{ __('Partnered') }}</option>
                                    </select>
                                </div>

                                {{-- Only a marriage that has ended can have an end date. --}}
                                <div class="flex-1 min-w-[12rem]"
                                     x-show="status !== 'married' && status !== 'partnered'" x-cloak>
                                    <x-input-label :for="'ended-'.$couple->id" :value="__('Ended on')" />
                                    <x-text-input :id="'ended-'.$couple->id" type="date" name="ended_on"
                                                  class="block w-full mt-1"
                                                  :value="old('ended_on', optional($couple->ended_on)->format('Y-m-d'))" />
                                </div>

                                <x-primary-button>{{ __('Save') }}</x-primary-button>
                            </div>

                            <x-input-error :messages="$errors->get('status')" />
                            <x-input-error :messages="$errors->get('ended_on')" />
                        </form>

                        {{-- Separate form: a nested one would not be valid
                             HTML, and this is a different verb on a different
                             route. Removing is for a marriage entered by
                             mistake — a real one that ended should be marked
                             divorced above, so it stays in the record. --}}
                        @if (auth()->user()->managesTree())
                            <form method="POST" action="{{ route('couples.destroy', $couple) }}"
                                  class="flex justify-end -mt-2"
                                  onsubmit="return confirm('Remove the marriage between {{ $person->full_name }} and {{ $marriage['partner']->full_name }} from the record? If the marriage was real but has ended, mark it divorced instead.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary text-xs">
                                    {{ __('Remove this marriage') }}
                                </button>
                            </form>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Custom fields --}}
            <div
                x-data='profileFields(@json($person->customFields), {{ $person->id }})'
                class="card p-6 space-y-4"
            >
                <div class="flex items-center justify-between border-b border-gold-light/30 pb-4">
                    <h3 class="font-serif text-xl text-content-hi">{{ __('More Fields') }}</h3>
                    <button type="button" @click="addField" class="btn btn-secondary text-sm">{{ __('Add field') }}</button>
                </div>

                <template x-for="(field, index) in fields" :key="field._key">
                    <div class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto_auto] gap-3 items-end border-b border-gold-light/10 pb-4">
                        <div>
                            <label class="block text-sm text-ink/70 mb-1">
                                {{ __('Label') }}
                                <input type="text" x-model="field.label" class="field mt-1 text-sm">
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm text-ink/70 mb-1">
                                {{ __('Value') }}
                                <input type="text" x-model="field.value" class="field mt-1 text-sm">
                            </label>
                        </div>
                        <div>
                            <label class="block text-sm text-ink/70 mb-1">
                                {{ __('Privacy') }}
                                <select x-model="field.visibility" class="field mt-1 text-xs">
                                    <option value="everyone">{{ __('Everyone') }}</option>
                                    <option value="family">{{ __('Family') }}</option>
                                    <option value="private">{{ __('Private') }}</option>
                                </select>
                            </label>
                        </div>
                        <div class="flex gap-2 pb-1">
                            <button type="button" @click="save(index)" :aria-label="`Save ${field.label || 'field'}`" class="btn btn-secondary text-xs px-3 py-1.5">{{ __('Save') }}</button>
                            <button type="button" @click="remove(index)" :aria-label="`Remove ${field.label || 'field'}`" class="btn btn-danger text-xs px-3 py-1.5">{{ __('Remove') }}</button>
                        </div>
                    </div>
                </template>

                <p x-show="fields.length === 0" class="text-sm text-ink/50">{{ __('No custom fields yet.') }}</p>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    document.addEventListener('alpine:init', () => {
        // Not crypto.randomUUID(): that API is only exposed in secure contexts
        // (HTTPS/localhost), and this app also runs over plain http:// on the
        // LAN vhost. This key never leaves the browser — it just gives
        // Alpine's x-for a stable :key, so Math.random() is sufficient.
        let nextKey = 0;
        const randomKey = () => `new-${Date.now()}-${++nextKey}`;

        Alpine.data('profileFields', (initialFields, personId) => ({
            fields: initialFields.map((f) => ({ ...f, _key: f.id ?? randomKey() })),

            addField() {
                this.fields.push({
                    _key: randomKey(),
                    id: null,
                    label: '',
                    field_type: 'text',
                    value: '',
                    visibility: 'family',
                });
            },

            async save(index) {
                const field = this.fields[index];
                const csrf = document.querySelector('meta[name="csrf-token"]').content;
                const isNew = !field.id;
                const url = isNew ? `${window.APP_URL}/people/${personId}/fields` : `${window.APP_URL}/fields/${field.id}`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        ...(isNew ? {} : { 'X-HTTP-Method-Override': 'PATCH' }),
                    },
                    body: JSON.stringify({
                        label: field.label,
                        field_type: field.field_type,
                        value: field.value,
                        visibility: field.visibility,
                    }),
                });

                if (response.ok) {
                    const saved = await response.json();
                    this.fields[index] = { ...saved, _key: field._key };
                } else {
                    const body = await response.json().catch(() => null);
                    alert(body?.message ?? 'Could not save this field.');
                }
            },

            async remove(index) {
                const field = this.fields[index];

                if (field.id) {
                    const csrf = document.querySelector('meta[name="csrf-token"]').content;
                    await fetch(`${window.APP_URL}/fields/${field.id}`, {
                        method: 'DELETE',
                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                    });
                }

                this.fields.splice(index, 1);
            },
        }));
    });
</script>
