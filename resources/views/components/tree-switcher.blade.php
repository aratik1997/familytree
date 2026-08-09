{{-- Only appears once somebody actually stands in more than one family. For
     everyone else there is nothing to choose between, and a control offering a
     single option is just a thing to wonder about. --}}
@php
    $user = auth()->user();
    $treeIds = $user?->accessibleTreeIds() ?? [];
@endphp

@if (count($treeIds) > 1)
    @php
        $trees = \App\Models\Tree::whereIn('id', $treeIds)->orderBy('name')->get();
        $current = $user->currentTreeId();
    @endphp

    <div class="flex items-center gap-1 text-xs">
        @foreach ($trees as $tree)
            @if ($tree->id === $current)
                <span class="px-2 py-1 rounded"
                      style="color: var(--text-hi); background: color-mix(in srgb, var(--gold-500) 18%, transparent)"
                      aria-current="true">{{ $tree->name }}</span>
            @else
                <a href="{{ route('trees.switch', $tree->id) }}"
                   class="px-2 py-1 rounded hover:underline"
                   style="color: var(--text-mid)">{{ $tree->name }}</a>
            @endif
        @endforeach
    </div>
@endif
