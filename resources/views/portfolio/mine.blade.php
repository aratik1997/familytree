@php
    $labels = [
        'name' => __('Full name'),
        'called' => __('Known as'),
        'role' => __('Role'),
        'profession' => __('Profession'),
        'tagline' => __('Introduction'),
        'speech' => __('Your words'),
    ];
    $long = ['tagline', 'speech'];

    // Blank rows to append when someone presses "Add". Shaped like the saved
    // ones so the same inputs render either way.
    $blank = [
        'roles' => ['icon' => '', 'title' => ['en' => '', 'bn' => ''], 'org' => ['en' => '', 'bn' => ''], 'note' => ['en' => '', 'bn' => '']],
        'education' => ['school' => '', 'where' => ['en' => '', 'bn' => '']],
        'languages' => ['name' => ['en' => '', 'bn' => ''], 'level' => ['en' => '', 'bn' => ''], 'v' => 60],
        'focus' => ['en' => '', 'bn' => ''],
    ];

    // Rows arrive in whatever shape the built page used; the editor needs
    // every box present, including ones that page never had.
    $shape = function (array $row, array $blank) {
        foreach ($blank as $key => $default) {
            if (! array_key_exists($key, $row) || $row[$key] === null) {
                $row[$key] = $default;
            } elseif (is_array($default)) {
                // 'org' is a plain string on some pages and a pair on others.
                $row[$key] = is_array($row[$key]) ? $row[$key] + $default : ['en' => $row[$key], 'bn' => $row[$key]];
            }
        }

        return $row;
    };

    $state = [
        'lists' => collect($form['lists'])
            ->map(fn ($rows, $name) => array_map(fn ($r) => $shape($r, $blank[$name]), $rows))
            ->all(),
        'sections' => $form['sections'],
        'blank' => $blank,
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Your portfolio') }}</p>
                <h2 class="text-2xl mt-1">{{ $form['fields']['name']['en'] ?: $slug }}</h2>
            </div>
            <a href="{{ $url }}" target="_blank" rel="noopener" class="btn btn-secondary">{{ __('View my page') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'portfolio-updated')
                <div class="card p-4 hairline" style="border-color: var(--accent)">
                    <p class="text-sm">
                        @if (session('publish_result') === 'published')
                            {{ __('Saved, and your live page has been updated.') }}
                        @else
                            {{ __('Saved. Your page will pick the change up when it is next published.') }}
                            <span style="color: var(--text-mid)">{{ session('publish_result') }}</span>
                        @endif
                    </p>
                </div>
            @endif

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('This is your own page, and only yours. Everything is shown in both languages — leave a box empty to keep whatever your page already says. What you save goes live straight away.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('my-portfolio.update') }}" class="space-y-5"
                  x-data="portfolioEditor(@js($state))">
                @csrf
                @method('PATCH')

                {{-- the wording --}}
                @foreach ($labels as $field => $label)
                    <div class="card p-6">
                        <h3 class="font-serif text-lg mb-4">{{ $label }}</h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach (['en' => __('English'), 'bn' => __('বাংলা')] as $lang => $langLabel)
                                <div>
                                    <x-input-label :for="$field.'_'.$lang" :value="$langLabel" />
                                    @if (in_array($field, $long, true))
                                        <textarea id="{{ $field }}_{{ $lang }}"
                                                  name="fields[{{ $field }}][{{ $lang }}]"
                                                  rows="4" class="field mt-1"
                                                  @if ($lang === 'bn') lang="bn" @endif
                                        >{{ old("fields.$field.$lang", $form['fields'][$field][$lang]) }}</textarea>
                                    @else
                                        <x-text-input :id="$field.'_'.$lang"
                                                      :name="'fields['.$field.']['.$lang.']'"
                                                      class="block mt-1 w-full"
                                                      :value="old('fields.'.$field.'.'.$lang, $form['fields'][$field][$lang])" />
                                    @endif
                                    <x-input-error :messages="$errors->get('fields.'.$field.'.'.$lang)" class="mt-1" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                {{-- the roles --}}
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                        <h3 class="font-serif text-lg">{{ __('What you do') }}</h3>
                        <button type="button" class="btn btn-secondary text-sm" @click="add('roles')">{{ __('Add a role') }}</button>
                    </div>
                    <p class="text-sm mb-4" style="color: var(--text-mid)">
                        {{ __('The positions listed on your page, in the order you want them read.') }}
                    </p>

                    <template x-if="!lists.roles.length">
                        <p class="text-sm py-2" style="color: var(--text-low)">{{ __('Nothing listed — this part of your page will be empty.') }}</p>
                    </template>

                    <template x-for="(row, i) in lists.roles" :key="row._id">
                        <div class="hairline p-4 mb-3" style="border-radius: var(--radius-control)">
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="eyebrow" x-text="'{{ __('Role') }} ' + (i + 1)"></span>
                                <div class="flex gap-1">
                                    <button type="button" class="btn btn-secondary text-xs" @click="move('roles', i, -1)" :disabled="i === 0">&uarr;</button>
                                    <button type="button" class="btn btn-secondary text-xs" @click="move('roles', i, 1)" :disabled="i === lists.roles.length - 1">&darr;</button>
                                    <button type="button" class="btn btn-secondary text-xs" @click="remove('roles', i)">{{ __('Remove') }}</button>
                                </div>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="block sm:col-span-2">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('Symbol') }}</span>
                                    <input type="text" class="field mt-1" maxlength="8" style="max-width: 6rem"
                                           :name="`lists[roles][${i}][icon]`" x-model="row.icon">
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('Title') }} — {{ __('English') }}</span>
                                    <input type="text" class="field mt-1" :name="`lists[roles][${i}][title][en]`" x-model="row.title.en">
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('Title') }} — {{ __('বাংলা') }}</span>
                                    <input type="text" lang="bn" class="field mt-1" :name="`lists[roles][${i}][title][bn]`" x-model="row.title.bn">
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('Where') }} — {{ __('English') }}</span>
                                    <input type="text" class="field mt-1" :name="`lists[roles][${i}][org][en]`" x-model="row.org.en">
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('Where') }} — {{ __('বাংলা') }}</span>
                                    <input type="text" lang="bn" class="field mt-1" :name="`lists[roles][${i}][org][bn]`" x-model="row.org.bn">
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('A line about it') }} — {{ __('English') }}</span>
                                    <textarea rows="2" class="field mt-1" :name="`lists[roles][${i}][note][en]`" x-model="row.note.en"></textarea>
                                </label>
                                <label class="block">
                                    <span class="text-xs" style="color: var(--text-mid)">{{ __('A line about it') }} — {{ __('বাংলা') }}</span>
                                    <textarea rows="2" lang="bn" class="field mt-1" :name="`lists[roles][${i}][note][bn]`" x-model="row.note.bn"></textarea>
                                </label>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- focus phrases --}}
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                        <h3 class="font-serif text-lg">{{ __('What you work on') }}</h3>
                        <button type="button" class="btn btn-secondary text-sm" @click="add('focus')">{{ __('Add') }}</button>
                    </div>
                    <p class="text-sm mb-4" style="color: var(--text-mid)">{{ __('Short phrases — a few words each.') }}</p>

                    <template x-for="(row, i) in lists.focus" :key="row._id">
                        <div class="grid gap-2 sm:grid-cols-[1fr_1fr_auto] items-end mb-2">
                            <input type="text" class="field" placeholder="{{ __('English') }}" :name="`lists[focus][${i}][en]`" x-model="row.en">
                            <input type="text" lang="bn" class="field" placeholder="{{ __('বাংলা') }}" :name="`lists[focus][${i}][bn]`" x-model="row.bn">
                            <button type="button" class="btn btn-secondary text-xs" @click="remove('focus', i)">{{ __('Remove') }}</button>
                        </div>
                    </template>
                </div>

                {{-- the education --}}
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                        <h3 class="font-serif text-lg">{{ __('Where you studied') }}</h3>
                        <button type="button" class="btn btn-secondary text-sm" @click="add('education')">{{ __('Add') }}</button>
                    </div>
                    <p class="text-sm mb-4" style="color: var(--text-mid)">
                        {{ __('Institution names stay as they are written; only the place beside them is translated.') }}
                    </p>

                    <template x-for="(row, i) in lists.education" :key="row._id">
                        <div class="grid gap-2 sm:grid-cols-[1.4fr_1fr_1fr_auto] items-end mb-2">
                            <input type="text" class="field" placeholder="{{ __('Institution') }}" :name="`lists[education][${i}][school]`" x-model="row.school">
                            <input type="text" class="field" placeholder="{{ __('Where') }} — {{ __('English') }}" :name="`lists[education][${i}][where][en]`" x-model="row.where.en">
                            <input type="text" lang="bn" class="field" placeholder="{{ __('Where') }} — {{ __('বাংলা') }}" :name="`lists[education][${i}][where][bn]`" x-model="row.where.bn">
                            <div class="flex gap-1">
                                <button type="button" class="btn btn-secondary text-xs" @click="move('education', i, -1)" :disabled="i === 0">&uarr;</button>
                                <button type="button" class="btn btn-secondary text-xs" @click="remove('education', i)">{{ __('Remove') }}</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- the languages --}}
                <div class="card p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-1">
                        <h3 class="font-serif text-lg">{{ __('Languages you speak') }}</h3>
                        <button type="button" class="btn btn-secondary text-sm" @click="add('languages')">{{ __('Add') }}</button>
                    </div>
                    <p class="text-sm mb-4" style="color: var(--text-mid)">{{ __('The bar on your page is drawn from the number.') }}</p>

                    <template x-for="(row, i) in lists.languages" :key="row._id">
                        <div class="hairline p-4 mb-3" style="border-radius: var(--radius-control)">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input type="text" class="field" placeholder="{{ __('Language') }} — {{ __('English') }}" :name="`lists[languages][${i}][name][en]`" x-model="row.name.en">
                                <input type="text" lang="bn" class="field" placeholder="{{ __('Language') }} — {{ __('বাংলা') }}" :name="`lists[languages][${i}][name][bn]`" x-model="row.name.bn">
                                <input type="text" class="field" placeholder="{{ __('How well') }} — {{ __('English') }}" :name="`lists[languages][${i}][level][en]`" x-model="row.level.en">
                                <input type="text" lang="bn" class="field" placeholder="{{ __('How well') }} — {{ __('বাংলা') }}" :name="`lists[languages][${i}][level][bn]`" x-model="row.level.bn">
                            </div>
                            <div class="flex flex-wrap items-center gap-3 mt-3">
                                <input type="range" min="0" max="100" step="5" class="flex-1" style="min-width: 10rem"
                                       :name="`lists[languages][${i}][v]`" x-model.number="row.v">
                                <span class="numeric text-sm" style="min-width: 3rem" x-text="row.v + '%'"></span>
                                <button type="button" class="btn btn-secondary text-xs" @click="move('languages', i, -1)" :disabled="i === 0">&uarr;</button>
                                <button type="button" class="btn btn-secondary text-xs" @click="remove('languages', i)">{{ __('Remove') }}</button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- the layout --}}
                <div class="card p-6">
                    <h3 class="font-serif text-lg">{{ __('The order of your page') }}</h3>
                    <p class="text-sm mb-4 measure" style="color: var(--text-mid)">
                        {{ __('Move a part up or down to change where it appears, or untick it to leave it off your page altogether.') }}
                    </p>

                    <template x-for="(s, i) in sections" :key="s.key">
                        <div class="flex items-center justify-between gap-3 hairline p-3 mb-2" style="border-radius: var(--radius-control)">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" value="1" :name="`sections[${i}][on]`" x-model="s.on">
                                <span :style="s.on ? '' : 'color: var(--text-low); text-decoration: line-through'" x-text="s.label"></span>
                            </label>
                            <div class="flex items-center gap-1">
                                <input type="hidden" :name="`sections[${i}][key]`" :value="s.key">
                                <button type="button" class="btn btn-secondary text-xs" @click="moveSection(i, -1)" :disabled="i === 0">&uarr;</button>
                                <button type="button" class="btn btn-secondary text-xs" @click="moveSection(i, 1)" :disabled="i === sections.length - 1">&darr;</button>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save and publish') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * The repeating parts of the portfolio editor.
             *
             * Rows carry a private _id so x-for keys on identity rather than
             * position: keyed by index, removing the second of four rows would
             * have the browser reuse the wrong inputs and the text would appear
             * to jump up a row.
             */
            function portfolioEditor(state) {
                let seq = 0;
                const withId = (row) => ({ ...structuredClone(row), _id: ++seq });

                return {
                    lists: Object.fromEntries(
                        Object.entries(state.lists).map(([name, rows]) => [name, rows.map(withId)])
                    ),
                    sections: state.sections,

                    add(name) {
                        this.lists[name].push(withId(state.blank[name]));
                    },
                    remove(name, i) {
                        this.lists[name].splice(i, 1);
                    },
                    move(name, i, by) {
                        const rows = this.lists[name];
                        const to = i + by;
                        if (to < 0 || to >= rows.length) return;
                        [rows[i], rows[to]] = [rows[to], rows[i]];
                    },
                    moveSection(i, by) {
                        const to = i + by;
                        if (to < 0 || to >= this.sections.length) return;
                        [this.sections[i], this.sections[to]] = [this.sections[to], this.sections[i]];
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
