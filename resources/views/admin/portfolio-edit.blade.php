<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Portfolios') }}</p>
                <h2 class="text-2xl mt-1">{{ $portfolio['icon'] }} {{ $form['fields']['name']['en'] ?: $portfolio['slug'] }}</h2>
            </div>
            <a href="{{ $portfolio['url'] ?? 'https://'.$portfolio['slug'].'.khandanilegacy.com/' }}"
               target="_blank" rel="noopener" class="btn btn-secondary">{{ __('View page') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('Everything on this person\'s page: the wording in both languages, what it lists, the photograph, and the order it reads in. Leave a text box empty to keep whatever the page already says.') }}
                </p>
                @if ($owner)
                    <p class="text-sm mt-3 measure" style="color: var(--text-mid)">
                        {{ __('This page belongs to :name, who can also edit it themselves.', ['name' => $owner]) }}
                    </p>
                @endif
            </div>

            <x-portfolio-editor
                :form="$form"
                :slug="$portfolio['slug']"
                :action="route('admin.portfolios.update', $portfolio['slug'])"
                :cancel="route('admin.portfolios.index')"
                :submit-label="__('Save changes')"
                :has-photo="$hasPhoto"
                :photo-url="$photoUrl" />

            <p class="text-xs text-right" style="color: var(--text-low)">
                {{ __('Saving records the change. It reaches the live page when you press Publish on the previous screen.') }}
            </p>
        </div>
    </div>
</x-app-layout>
