@php
    $labels = [
        'name' => __('Full name'),
        'called' => __('Known as'),
        'role' => __('Role'),
        'profession' => __('Profession'),
        'tagline' => __('Introduction'),
        'speech' => __('Their words'),
    ];
    $long = ['tagline', 'speech'];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Portfolios') }}</p>
                <h2 class="text-2xl mt-1">{{ $portfolio['icon'] }} {{ $values['name']['en'] ?: $portfolio['slug'] }}</h2>
            </div>
            <a href="{{ $portfolio['url'] ?? 'https://'.$portfolio['slug'].'.khandanilegacy.com/' }}"
               target="_blank" rel="noopener" class="btn btn-secondary">{{ __('View page') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('Both languages are shown side by side. Leave a box empty to keep whatever the page already says — an empty box is not the same as deleting a line.') }}
                </p>
            </div>

            <form method="POST" action="{{ route('admin.portfolios.update', $portfolio['slug']) }}" class="space-y-5">
                @csrf
                @method('PATCH')

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
                                        >{{ old("fields.$field.$lang", $values[$field][$lang]) }}</textarea>
                                    @else
                                        <x-text-input :id="$field.'_'.$lang"
                                                      :name="'fields['.$field.']['.$lang.']'"
                                                      class="block mt-1 w-full"
                                                      :value="old('fields.'.$field.'.'.$lang, $values[$field][$lang])" />
                                    @endif
                                    <x-input-error :messages="$errors->get('fields.'.$field.'.'.$lang)" class="mt-1" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex flex-wrap justify-end gap-2">
                    <a href="{{ route('admin.portfolios.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Save changes') }}</x-primary-button>
                </div>

                <p class="text-xs text-right" style="color: var(--text-low)">
                    {{ __('Saving records the change. It reaches the live page when you press Publish on the previous screen.') }}
                </p>
            </form>
        </div>
    </div>
</x-app-layout>
