<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">{{ __('Family tree') }}</p>
        <h2 class="text-2xl mt-1">{{ __('Standing in another family') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('membership-requested') {{ __('Asked. They will see it when they next sign in, and nothing shows on your tree until they agree.') }} @break
                        @case('membership-accepted') {{ __('Agreed. You now stand in their tree as well as your own.') }} @break
                        @case('membership-declined') {{ __('Declined. Nothing has changed, and they are not told why.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            {{-- Waiting on this person's answer. First, because it is the only
                 thing on this page that somebody else is held up by. --}}
            @if ($waitingOnMe->isNotEmpty())
                <div class="card p-6 space-y-4">
                    <div>
                        <h3 class="font-serif text-xl">{{ __('Waiting for your answer') }}</h3>
                        <p class="text-sm measure mt-1" style="color: var(--text-mid)">
                            {{ __('Agreeing places you in their tree using the profile you already have — the same photo and details, and the same choices about who sees what. Nothing of yours is copied, and you can look at their tree as well as your own.') }}
                        </p>
                    </div>

                    @foreach ($waitingOnMe as $membership)
                        <div class="hairline p-4 space-y-3" style="border-radius: var(--radius-control)">
                            <p>
                                <span class="font-medium">{{ $membership->invitedBy?->full_name ?? __('Someone') }}</span>
                                {{ __('would like to place you in') }}
                                <span class="font-medium">{{ $membership->tree?->name }}</span>.
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('memberships.accept', $membership) }}">
                                    @csrf
                                    <x-primary-button>{{ __('Agree') }}</x-primary-button>
                                </form>
                                <form method="POST" action="{{ route('memberships.decline', $membership) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-secondary">{{ __('Decline') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Your own ID, so it can be passed to a relative who keeps a
                 different tree. --}}
            @if (auth()->user()->person)
                <div class="card p-6">
                    <h3 class="font-serif text-xl">{{ __('Your ID') }}</h3>
                    <p class="text-sm measure mt-1" style="color: var(--text-mid)">
                        {{ __('Give this to a relative who keeps a tree of their own and they can ask to place you in it. It is the only way to be found — nobody can search for you by name.') }}
                    </p>
                    <p class="numeric text-2xl mt-3" style="color: var(--gold-text)">{{ auth()->user()->person->public_id }}</p>
                </div>
            @endif

            <div class="card p-6 space-y-4">
                <div>
                    <h3 class="font-serif text-xl">{{ __('Add someone to this tree') }}</h3>
                    <p class="text-sm measure mt-1" style="color: var(--text-mid)">
                        {{ __('For a relative who is already in a tree of their own — a son-in-law, a daughter who married into another family. Ask them for their ID. They keep their own profile, and nothing shows here until they agree.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('memberships.store') }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="flex-1 min-w-[14rem]">
                        <x-input-label for="public_id" :value="__('Their ID')" />
                        <x-text-input id="public_id" name="public_id" class="block mt-1 w-full numeric"
                                      placeholder="FT-XXXXXX" :value="old('public_id')" required />
                        <x-input-error :messages="$errors->get('public_id')" class="mt-2" />
                    </div>
                    <x-primary-button>{{ __('Ask them') }}</x-primary-button>
                </form>
            </div>

            @if ($fromMyTree->isNotEmpty())
                <div class="card p-6 space-y-3">
                    <h3 class="font-serif text-xl">{{ __('People asked into this tree') }}</h3>

                    @foreach ($fromMyTree as $membership)
                        <div class="hairline p-3 flex flex-wrap items-center justify-between gap-2"
                             style="border-radius: var(--radius-control)">
                            <span>{{ $membership->person?->full_name ?? __('Someone') }}</span>
                            <span class="text-xs" style="color: var(--text-low)">
                                @switch($membership->status)
                                    @case('pending') {{ __('waiting for their answer') }} @break
                                    @case('accepted') {{ __('in this tree') }} @break
                                    @case('declined') {{ __('declined') }} @break
                                @endswitch
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
