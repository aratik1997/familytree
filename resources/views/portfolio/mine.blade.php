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
                        @if (str_starts_with((string) session('publish_result'), 'published'))
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

            <x-portfolio-editor
                :form="$form"
                :slug="$slug"
                :action="route('my-portfolio.update')"
                :cancel="route('dashboard')"
                :submit-label="__('Save and publish')"
                :speech-label="__('Your words')"
                :has-photo="$hasPhoto"
                :photo-url="$photoUrl" />
        </div>
    </div>
</x-app-layout>
