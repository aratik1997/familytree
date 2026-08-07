<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Super Admin') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Admins') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Family members') }}</a>
                <a href="{{ route('admin.admins.create') }}" class="btn btn-primary">{{ __('Add admin') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('admin-created') {{ __('Admin added, and their invitation has gone out.') }} @break
                        @case('admin-updated') {{ __('Admin updated.') }} @break
                        @case('admin-invited') {{ __('Invitation sent again.') }} @break
                        @case('admin-removed') {{ __('Admin removed, along with their tree.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('Each Admin keeps a family tree of their own. Inside it they can do everything you can do inside yours — add people, edit anyone, change who is related to whom. They cannot see your tree, and you cannot see theirs.') }}
                </p>
            </div>

            @forelse ($admins as $admin)
                <div class="card p-6 space-y-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-serif text-xl">{{ $admin->name }}</p>
                            <p class="text-sm numeric" style="color: var(--text-mid)">{{ $admin->email }}</p>
                            <p class="text-sm mt-1" style="color: var(--text-low)">
                                {{ $admin->tree?->name ?? __('No tree') }}
                                @if ($admin->person_count === 0)
                                    · <span style="color: var(--warning)">{{ __('not started yet') }}</span>
                                @endif
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('admin.admins.edit', $admin) }}" class="btn btn-secondary text-xs">
                                {{ __('Edit') }}
                            </a>

                            <form method="POST" action="{{ route('admin.admins.resend-invite', $admin) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary text-xs">
                                    {{ __('Resend invitation') }}
                                </button>
                            </form>

                            {{-- The tree goes with them: its people exist
                                 nowhere else, and nobody else can see them. --}}
                            <form method="POST" action="{{ route('admin.admins.destroy', $admin) }}"
                                  onsubmit="return confirm('{{ __('Remove :name? Their whole family tree goes with them, and nobody else can see it — it cannot be recovered.', ['name' => $admin->name]) }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-secondary text-xs">{{ __('Remove') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card p-6">
                    <p style="color: var(--text-low)">{{ __('No admins yet. Add one and they will get their own tree to build.') }}</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
