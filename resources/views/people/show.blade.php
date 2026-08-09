@use('App\Support\FieldVisibility')

@php
$viewer = auth()->user();
$canEdit = $viewer->can('update', $person);
$show = fn (string $field) => FieldVisibility::canSee($viewer, $person, FieldVisibility::visibilityFor($person, $field));
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-2xl">
                {{ __('Profile') }}
            </h2>
            <div class="flex flex-wrap gap-3">
                @if ($canEdit)
                    <a href="{{ route('people.children.create', $person) }}" class="btn btn-secondary">{{ __('Add child') }}</a>
                    @if ($viewer->managesTree())
                        <a href="{{ route('admin.people.parents.create', $person) }}" class="btn btn-secondary">{{ __('Add parent') }}</a>
                        <a href="{{ route('admin.people.spouses.create', $person) }}" class="btn btn-secondary">{{ __('Add spouse') }}</a>
                    @endif
                    <a href="{{ route('people.edit', $person) }}" class="btn btn-primary">{{ __('Edit profile') }}</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('child-added') {{ __('Child added.') }} @break
                        @case('person-added') {{ __('Person added.') }} @break
                        @case('parent-added') {{ __('Parent added.') }} @break
                        @case('spouse-added') {{ __('Spouse added.') }} @break
                        @case('profile-updated') {{ __('Profile updated.') }} @break
                        @case('photo-updated') {{ __('Photo updated.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            {{-- The hero is the one place the arch silhouette and the corner
                 filigree appear together; nothing else on this page adds a
                 third ornament. --}}
            <div class="card relative p-6">
                <x-ornament.filigree corner="tl" />
                <x-ornament.filigree corner="br" />

                <div class="flex flex-wrap items-center gap-6">
                    @if ($show('profile_photo_path') && $person->profile_photo_path)
                        <div class="arch-clip shrink-0"
                             style="width: 7rem; height: 7rem; border: 2px {{ $person->isMinor() ? 'dashed' : 'solid' }} var(--gold-500)">
                            <img src="{{ $person->photo_url }}"
                                 class="w-full h-full object-cover"
                                 style="{{ $person->is_deceased ? 'filter: saturate(.35)' : '' }}"
                                 alt="{{ $person->full_name }}">
                        </div>
                    @else
                        <div class="arch-clip shrink-0 flex items-center justify-center"
                             style="width: 7rem; height: 7rem; background: var(--ink-600); border: 2px solid var(--gold-500); color: var(--text-low)">
                            <x-application-logo class="w-10 h-10" />
                        </div>
                    @endif

                    <div class="min-w-0">
                        @if ($show('full_name'))
                            <h1 class="font-serif text-3xl">{{ $person->full_name }}</h1>
                        @endif

                        @php
                            $birthYear = $person->date_of_birth?->format('Y');
                            $deathYear = $person->death_date?->format('Y');
                        @endphp
                        @if ($birthYear || $deathYear)
                            <p class="numeric text-sm mt-1" style="color: var(--text-mid)">
                                @if ($person->is_deceased)
                                    {{ $birthYear ?? '—' }} – {{ $deathYear ?? '—' }} <span aria-hidden="true">✦</span>
                                @else
                                    {{ __('b.') }} {{ $birthYear }}
                                @endif
                            </p>
                        @endif

                        <div class="flex flex-wrap items-center gap-2 mt-2">
                            @if ($person->gender === 'male')
                                <span class="chip chip-male"><span aria-hidden="true">♂</span> {{ __('Male') }}</span>
                            @elseif ($person->gender === 'female')
                                <span class="chip chip-female"><span aria-hidden="true">♀</span> {{ __('Female') }}</span>
                            @endif

                            @if ($person->isMinor())
                                <span class="privacy-badge-family">{{ __('Minor — managed by a parent') }}</span>
                            @endif

                            {{-- Shown only to the person themselves and to
                                 whoever runs this tree. It is how another
                                 family asks for them, so handing it to every
                                 visitor would hand out the means to pester
                                 them. --}}
                            @if ($viewer->person?->id === $person->id || $viewer->managesTree())
                                <span class="chip numeric" title="{{ __('Give this to a relative who keeps their own tree') }}">
                                    {{ $person->public_id }}
                                </span>
                            @endif

                            {{-- Lent from another family: their record lives in
                                 their own tree and is not ours to edit. --}}
                            @if (! $viewer->ownsRecordTree($person))
                                <span class="privacy-badge-family">{{ __('From another family') }}</span>
                            @endif
                        </div>

                        @if ($person->is_deceased)
                            <p class="text-sm italic mt-2" style="color: var(--maroon-500)">{{ __('In loving memory') }}</p>
                        @endif
                    </div>
                </div>

                @if ($show('bio') && $person->bio)
                    <p class="mt-6 measure whitespace-pre-line" style="color: var(--text-mid)">{{ $person->bio }}</p>
                @endif
            </div>

            <div class="card p-6">
                <h3 class="font-serif text-xl text-content-hi mb-4">{{ __('Details') }}</h3>
                <dl class="divide-y divide-gold-light/20">
                    @if ($show('email'))
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-sm text-ink/50">{{ __('Email') }}</dt>
                            <dd class="text-sm text-ink">{{ $person->email }}</dd>
                        </div>
                    @endif
                    @if ($show('mobile') && $person->mobile)
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-sm text-ink/50">{{ __('Mobile') }}</dt>
                            <dd class="text-sm text-ink">{{ $person->mobile }}</dd>
                        </div>
                    @endif
                    @if ($show('address') && $person->address)
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-sm text-ink/50">{{ __('Address') }}</dt>
                            <dd class="text-sm text-ink">{{ $person->address }}</dd>
                        </div>
                    @endif
                    @if ($show('date_of_birth'))
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-sm text-ink/50">{{ __('Born') }}</dt>
                            <dd class="text-sm numeric">{{ $person->date_of_birth->format('Y-m-d') }}</dd>
                        </div>
                    @endif
                    @if ($show('social_links') && filled($person->social_links))
                        <div class="py-3 flex justify-between gap-4">
                            <dt class="text-sm text-ink/50">{{ __('Social media') }}</dt>
                            <dd class="text-sm text-ink text-right space-y-1">
                                @foreach ($person->social_links as $label => $url)
                                    @if ($url)
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="block underline hover:text-royal">{{ ucfirst($label) }}</a>
                                    @endif
                                @endforeach
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>

            @php
                $visibleCustomFields = $person->customFields->filter(
                    fn ($field) => FieldVisibility::canSee($viewer, $person, $field->visibility)
                );
            @endphp

            @if ($visibleCustomFields->isNotEmpty())
                <div class="card p-6">
                    <h3 class="font-serif text-xl text-content-hi mb-4">{{ __('More About') }} {{ $person->full_name }}</h3>
                    <dl class="divide-y divide-gold-light/20">
                        @foreach ($visibleCustomFields as $field)
                            <div class="py-3 flex justify-between gap-4">
                                <dt class="text-sm text-ink/50">{{ $field->label }}</dt>
                                <dd class="text-sm text-ink text-right">{{ $field->value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endif

            @php
                $recordTypes = [
                    'academic' => __('Academic'),
                    'photo' => __('Family Photos'),
                    'moment' => __('Moments'),
                    'career' => __('Career'),
                    'other' => __('Others'),
                ];
                $visibleRecords = $person->records->filter(
                    fn ($record) => FieldVisibility::canSee($viewer, $person, $record->visibility)
                );
            @endphp

            <div class="card p-6" x-data="{ tab: 'academic', showForm: false }">
                <div class="flex items-center justify-between border-b border-gold-light/30 pb-4 mb-4">
                    <h3 class="font-serif text-xl text-content-hi">{{ __('Records') }}</h3>
                    @if ($canEdit)
                        <button type="button" @click="showForm = !showForm" class="btn btn-secondary text-sm">
                            {{ __('Add record') }}
                        </button>
                    @endif
                </div>

                <div class="flex flex-wrap gap-2 mb-5">
                    @foreach ($recordTypes as $key => $label)
                        <button
                            type="button"
                            @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-royal text-content-hi' : 'bg-parchment-dark text-ink/60'"
                            class="px-3 py-1.5 rounded-md text-xs font-medium"
                        >{{ $label }} ({{ $visibleRecords->where('type', $key)->count() }})</button>
                    @endforeach
                </div>

                @if ($canEdit)
                    <form
                        x-show="showForm"
                        x-cloak
                        method="POST"
                        action="{{ route('records.store', $person) }}"
                        enctype="multipart/form-data"
                        class="mb-6 p-4 rounded-md bg-parchment-dark/40 space-y-3"
                        x-data="{ type: 'academic' }"
                    >
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <x-input-label :value="__('Type')" />
                                <select name="type" x-model="type" class="field mt-1 text-sm">
                                    @foreach ($recordTypes as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <x-input-label :value="__('Privacy')" />
                                <x-privacy-select name="visibility" selected="family" class="block mt-1 w-full" />
                            </div>
                        </div>

                        <div>
                            <x-input-label :value="__('Title')" />
                            <x-text-input name="title" class="block mt-1 w-full" required />
                        </div>

                        <div>
                            <x-input-label :value="__('Description')" />
                            <textarea name="description" rows="2" class="field mt-1 text-sm"></textarea>
                        </div>

                        <div>
                            <x-input-label :value="__('Date')" />
                            <x-text-input type="date" name="occurred_on" class="block mt-1 w-full sm:w-64" />
                        </div>

                        <div x-show="type === 'academic'" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <x-text-input name="meta[institution]" placeholder="{{ __('Institution') }}" />
                            <x-text-input name="meta[degree]" placeholder="{{ __('Degree') }}" />
                            <x-text-input name="meta[grade]" placeholder="{{ __('Grade') }}" />
                        </div>

                        <div x-show="type === 'career'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <x-text-input name="meta[company]" placeholder="{{ __('Company') }}" />
                            <x-text-input name="meta[role]" placeholder="{{ __('Role') }}" />
                        </div>

                        <div>
                            <x-input-label :value="__('Photos')" />
                            <input type="file" name="photos[]" multiple accept="image/*" class="block mt-1 text-sm">
                        </div>

                        <div class="flex justify-end">
                            <x-primary-button>{{ __('Save Record') }}</x-primary-button>
                        </div>
                    </form>
                @endif

                @foreach ($recordTypes as $key => $label)
                    <div x-show="tab === '{{ $key }}'" class="space-y-4">
                        @forelse ($visibleRecords->where('type', $key) as $record)
                            <div class="border border-gold-light/20 rounded-md p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="font-serif text-lg text-content-hi">{{ $record->title }}</p>
                                        @if ($record->occurred_on)
                                            <p class="text-xs numeric" style="color: var(--text-low)">{{ $record->occurred_on->format('Y-m-d') }}</p>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-privacy-badge :visibility="$record->visibility" />
                                        @if ($canEdit)
                                            <form method="POST" action="{{ route('records.destroy', $record) }}" onsubmit="return confirm('{{ __('Remove this record?') }}')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-ruby text-xs hover:underline">{{ __('Remove') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>

                                @if ($record->description)
                                    <p class="mt-2 text-sm text-ink/80 whitespace-pre-line">{{ $record->description }}</p>
                                @endif

                                @if (filled($record->meta))
                                    <dl class="mt-3 grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach ($record->meta as $metaKey => $metaValue)
                                            @if ($metaValue)
                                                <div>
                                                    <dt class="text-xs text-ink/40">{{ ucfirst($metaKey) }}</dt>
                                                    <dd class="text-sm text-ink">{{ $metaValue }}</dd>
                                                </div>
                                            @endif
                                        @endforeach
                                    </dl>
                                @endif

                                @if ($record->media->isNotEmpty())
                                    <div class="mt-3 grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        @foreach ($record->media as $media)
                                            <img src="{{ $media->url }}" class="w-full h-24 object-cover rounded-md" alt="">
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-ink/40">{{ __('Nothing here yet.') }}</p>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
