<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Portfolios') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Family members') }}</a>
                <form method="POST" action="{{ route('admin.portfolios.publish') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">{{ __('Publish changes') }}</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status') === 'portfolio-saved')
                <div class="card p-4 text-emerald text-sm">
                    {{ __('Saved. Press Publish to put it on the live page.') }}
                </div>
            @endif

            {{-- Publishing reports per page rather than as one message: a folder
                 that is missing on this server is the difference between "done"
                 and "done for seven of them". --}}
            @if (session('publish_result'))
                <div class="card p-5">
                    <p class="eyebrow mb-3">{{ __('Published') }}</p>
                    <div class="grid gap-1 text-sm">
                        @foreach (session('publish_result') as $slug => $outcome)
                            <div class="flex justify-between gap-4 py-1 hairline-b">
                                <span>{{ $slug }}</span>
                                <span class="numeric text-xs"
                                      style="color: {{ $outcome === 'published' ? 'var(--emerald-500, var(--gold-text))' : 'var(--warning)' }}">
                                    {{ $outcome }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('Edit the wording of any page here, in English and Bangla. Changes are saved as you go and reach the live pages when you press Publish. Layout, colours and sections are part of the built page and are not editable here.') }}
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($portfolios as $p)
                    <div class="card p-5">
                        <div class="flex items-start gap-4">
                            <span class="text-3xl leading-none" aria-hidden="true">{{ $p['icon'] }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-serif text-lg leading-tight">{{ $p['name']['en'] }}</p>
                                <p class="text-sm mt-0.5" style="color: var(--text-mid)">
                                    {{ $p['profession']['en'] }}
                                </p>

                                <div class="flex flex-wrap items-center gap-2 mt-2">
                                    @if ($p['edited'] && ! $p['published'])
                                        <span class="privacy-badge-family">{{ __('edited, not published') }}</span>
                                    @elseif ($p['published'])
                                        <span class="privacy-badge-everyone">{{ __('published') }}</span>
                                    @else
                                        <span class="privacy-badge-private">{{ __('as built') }}</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-2 mt-4">
                                    <a href="{{ route('admin.portfolios.edit', $p['slug']) }}" class="btn btn-primary text-xs px-3 py-1.5">
                                        {{ __('Edit') }}
                                    </a>
                                    <a href="{{ $p['url'] }}" target="_blank" rel="noopener" class="btn btn-secondary text-xs px-3 py-1.5">
                                        {{ __('View') }} ↗
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- What this page cannot do, said plainly, so nobody hunts for a
                 button that does not exist. --}}
            <div class="card p-6">
                <h3 class="font-serif text-xl">{{ __('What needs a rebuild') }}</h3>
                <p class="text-sm measure mt-2" style="color: var(--text-mid)">
                    {{ __('Wording is editable here. Anything structural — a new section, a different colour, a photograph — lives in the built page and needs the project rebuilt and uploaded.') }}
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
