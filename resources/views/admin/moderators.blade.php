<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Super Admin') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Moderators') }}</h2>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">{{ __('Family members') }}</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('moderator-added') {{ __('They can now look after the tree.') }} @break
                        @case('moderator-removed') {{ __('They no longer look after the tree.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            <div class="card p-6">
                <p class="text-sm measure" style="color: var(--text-mid)">
                    {{ __('A moderator can do everything you can with the family records — add people, edit anyone\'s profile, change who is related to whom, send invitations. What they cannot do is appoint other moderators. Only someone who has claimed their account can be made one.') }}
                </p>
            </div>

            <div class="card p-0 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="hairline-b">
                            <th class="text-left p-4 font-medium" style="color: var(--text-low)">{{ __('Name') }}</th>
                            <th class="text-left p-4 font-medium" style="color: var(--text-low)">{{ __('Role') }}</th>
                            <th class="p-4"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($candidates as $person)
                            @php($user = $person->user)
                            <tr class="hairline-b">
                                <td class="p-4">
                                    <a href="{{ route('people.show', $person) }}" class="hover:underline">{{ $person->full_name }}</a>
                                    <div class="text-xs numeric" style="color: var(--text-low)">{{ $person->email }}</div>
                                </td>
                                <td class="p-4">
                                    @if ($user->is_super_admin)
                                        <span class="chip">{{ __('Super Admin') }}</span>
                                    @elseif ($user->is_moderator)
                                        <span class="chip">{{ __('Moderator') }}</span>
                                    @else
                                        <span style="color: var(--text-low)">{{ __('Member') }}</span>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    @if ($user->is_super_admin)
                                        {{-- Their standing does not come from this flag, so
                                             offering to change it would be offering nothing. --}}
                                        <span class="text-xs" style="color: var(--text-low)">{{ __('always') }}</span>
                                    @elseif ($user->is_moderator)
                                        <form method="POST" action="{{ route('admin.moderators.demote', $user) }}"
                                              onsubmit="return confirm('{{ __('Stop :name looking after the tree?', ['name' => $person->full_name]) }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-secondary text-xs">{{ __('Remove') }}</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.moderators.promote', $user) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-secondary text-xs">{{ __('Make moderator') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-6" style="color: var(--text-low)">
                                    {{ __('Nobody has claimed an account yet, so there is no one to appoint.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
