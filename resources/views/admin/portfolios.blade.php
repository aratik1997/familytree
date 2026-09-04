<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Portfolios') }}</h2>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Family members') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('Each of the eight has a page of their own on its own address. They are built from the portfolios folder in the project and served as files, so there is nothing to edit here — this is where to find them.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($portfolios as $p)
                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener"
                       class="card p-5 flex items-start gap-4 transition-transform hover:-translate-y-1">
                        <span class="text-3xl leading-none" aria-hidden="true">{{ $p['icon'] }}</span>
                        <div class="min-w-0">
                            <p class="font-serif text-lg leading-tight">{{ $p['name'] }}</p>
                            @if ($p['called'])
                                <p class="text-xs italic" style="color: var(--text-low)">
                                    {{ __('known as') }} {{ $p['called'] }}
                                </p>
                            @endif
                            <p class="text-sm mt-1" style="color: var(--text-mid)">
                                {{ $p['work'] }} · {{ $p['role'] }}
                            </p>
                            <p class="text-xs numeric mt-2 truncate" style="color: var(--gold-text)">
                                {{ $p['slug'] }}.khandanilegacy.com ↗
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>

            {{-- How a change actually reaches these pages. Written down because
                 it is not guessable from the admin area: they are static files,
                 not rows, and nothing here republishes them. --}}
            <div class="card p-6">
                <h3 class="font-serif text-xl">{{ __('Changing what a page says') }}</h3>
                <p class="text-sm measure mt-2" style="color: var(--text-mid)">
                    {{ __('The words for all eight, in both languages, live in one file: portfolios/src/data.js. After an edit the pages have to be rebuilt and uploaded — they are not read from this database.') }}
                </p>
                <pre class="mt-4 p-4 text-xs overflow-x-auto hairline" style="border-radius: var(--radius-control); color: var(--text-mid)"><code>cd portfolios
npm run build
bash scripts/deploy.sh</code></pre>
                <p class="text-xs mt-3" style="color: var(--text-low)">
                    {{ __('Photographs go in portfolios/public/img, named after the person — ansary.jpg and so on — then rebuild.') }}
                </p>
            </div>
        </div>
    </div>
</x-app-layout>
