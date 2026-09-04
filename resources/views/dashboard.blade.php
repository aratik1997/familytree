@vite('resources/js/dashboard-tree.js')

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('The Khandani Legacy') }}</p>
            <h2 class="text-2xl mt-1">{{ __('Dashboard') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (auth()->user()->person)
                <div class="card card-hover p-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('Your place in the family') }}</p>
                        <p class="font-serif text-xl mt-1">{{ auth()->user()->person->full_name }}</p>
                        @if (auth()->user()->person->date_of_birth)
                            <p class="numeric text-sm mt-0.5" style="color: var(--text-mid)">
                                {{ __('b.') }} {{ auth()->user()->person->date_of_birth->format('Y') }}
                            </p>
                        @endif
                    </div>
                    <a href="{{ route('people.show', auth()->user()->person) }}" class="btn btn-primary">
                        {{ __('View my profile') }}
                    </a>
                </div>
            @endif

            @if ($portfolio = App\Support\PortfolioOwners::slugFor(auth()->user()))
                <div class="card card-hover p-6 flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="eyebrow">{{ __('Your own website') }}</p>
                        <p class="font-serif text-xl mt-1">{{ $portfolio }}.khandanilegacy.com</p>
                        <p class="text-sm mt-0.5" style="color: var(--text-mid)">
                            {{ __('Change what it says, what it lists, and the order it reads in.') }}
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="https://{{ $portfolio }}.khandanilegacy.com/" target="_blank" rel="noopener"
                           class="btn btn-secondary">{{ __('View') }}</a>
                        <a href="{{ route('my-portfolio.edit') }}" class="btn btn-primary">{{ __('Edit portfolio') }}</a>
                    </div>
                </div>
            @endif

            <div class="card p-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <p class="eyebrow">{{ __('Everyone, at a glance') }}</p>
                        <h3 class="font-serif text-xl mt-1">{{ __('The family tree') }}</h3>
                    </div>
                    <a href="{{ route('tree.index') }}" class="btn btn-secondary text-sm">{{ __('Open the full tree') }}</a>
                </div>

                <div class="relative overflow-hidden hairline" style="height: 360px; border-radius: var(--radius-control); background: var(--ink-900)">
                    <p id="dashboard-tree-status" class="p-4 text-sm" style="color: var(--text-low)">{{ __('Growing the tree…') }}</p>
                    <div id="dashboard-tree-canvas" class="w-full h-full"></div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
