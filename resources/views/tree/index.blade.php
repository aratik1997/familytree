@vite('resources/js/tree.js')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('The family') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Family tree') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->canManageTree())
                    <a href="{{ route('admin.people.create') }}" class="btn btn-secondary">{{ __('Add person') }}</a>
                    <button type="button" id="tree-reset" class="btn btn-secondary">
                        {{ __('Reset tree') }}
                    </button>
                @endif
                <button type="button" id="tree-toggle-3d" class="btn btn-secondary">
                    {{ __('Showcase in 3D') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="card p-3 text-xs measure" style="color: var(--text-low)">
                @if (auth()->user()->canManageTree())
                    {{ __('Drag a person onto another to link them as parent and child, or along their generation to rearrange. Click a branch to pick it out, or a gold marriage line to see just that family.') }}
                @else
                    {{ __('Drag a person along their generation to rearrange your view, click a branch to pick it out, or a gold marriage line to see just that family. Nothing you do here is saved — only a Super Admin can change the family record.') }}
                @endif
            </div>

            <div id="tree-focus" class="hidden card p-3 flex-wrap items-center justify-between gap-3">
                <p class="text-sm" style="color: var(--text-mid)">
                    {{ __('Showing the family of') }}
                    <span id="tree-focus-label" class="font-serif font-semibold" style="color: var(--text-hi)"></span>
                </p>
                <button type="button" id="tree-focus-clear" class="btn btn-secondary text-xs">
                    {{ __('Back to the whole tree') }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs" style="color: var(--text-mid)">
                <span class="inline-flex items-center gap-1.5">
                    <span aria-hidden="true" style="color: var(--male-500)">♂</span>
                    <span class="inline-block w-3 h-3 rounded-full" style="border: 2px solid var(--male-500)"></span>
                    {{ __('Male') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span aria-hidden="true" style="color: var(--female-500)">♀</span>
                    <span class="inline-block w-3 h-3 rounded-full" style="border: 2px solid var(--female-500)"></span>
                    {{ __('Female') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block w-3 h-3 rounded-full" style="border: 2px dashed var(--gold-500)"></span>
                    {{ __('Not yet claimed') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span aria-hidden="true" style="color: var(--maroon-500)">✦</span>
                    {{ __('Deceased') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block w-4 h-0.5" style="background: var(--gold-500)"></span>
                    {{ __('Marriage') }}
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block w-4" style="border-top: 2px dashed var(--leaf-600)"></span>
                    {{ __('Step or adoptive') }}
                </span>
            </div>

            <div class="card relative overflow-hidden" style="height: 72vh">
                <p id="tree-status" class="p-6 text-sm" style="color: var(--text-low)">{{ __('Growing the tree…') }}</p>
                <div id="tree-canvas" class="w-full h-full"></div>
            </div>
        </div>
    </div>
</x-app-layout>
