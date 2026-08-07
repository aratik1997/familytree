<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="eyebrow">{{ __('Admin') }}</p>
                <h2 class="text-2xl mt-1">{{ __('Family members') }}</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                @if (auth()->user()->is_super_admin)
                    <a href="{{ route('admin.admins.index') }}" class="btn btn-secondary">{{ __('Admins') }}</a>
                @endif
                <a href="{{ route('admin.mail-check') }}" class="btn btn-secondary">{{ __('Email check') }}</a>
                <a href="{{ route('admin.people.create') }}" class="btn btn-primary">{{ __('Add person') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') && ! session('invite_link'))
                <div class="card p-4 text-emerald text-sm">
                    @switch(session('status'))
                        @case('password-updated') {{ __('Password updated.') }} @break
                        @case('invite-sent') {{ __('Invitation sent.') }} @break
                        @case('person-removed') {{ __('Person removed.') }} @break
                        @default {{ __(session('status')) }}
                    @endswitch
                </div>
            @endif

            {{--
                Shown once, immediately after sending. Only a hash of the token
                is kept, so this is the one moment the real link can be copied
                — reloading the page loses it for good.
            --}}
            @if (session('invite_link'))
                <div class="card p-5 space-y-3"
                     x-data="{
                         link: @js(session('invite_link')),
                         copied: false,
                         async copy() {
                             try {
                                 await navigator.clipboard.writeText(this.link);
                             } catch (e) {
                                 // Clipboard access needs HTTPS and permission;
                                 // fall back to selecting it so Ctrl+C works.
                                 this.$refs.field.select();
                                 document.execCommand('copy');
                             }
                             this.copied = true;
                             setTimeout(() => { this.copied = false; }, 2500);
                         },
                     }">
                    <div>
                        <p class="eyebrow">{{ __('Invitation sent') }}</p>
                        <p class="text-sm mt-1" style="color: var(--text-mid)">
                            {{ __('Emailed to :email. You can also pass this link on yourself — it works until :date and can be used once.', [
                                'email' => session('invite_email'),
                                'date' => session('invite_expires'),
                            ]) }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <input type="text" readonly x-ref="field" :value="link"
                               class="field flex-1 text-xs"
                               style="font-family: var(--font-mono); min-width: 16rem"
                               onclick="this.select()"
                               aria-label="{{ __('Invitation link for :name', ['name' => session('invite_for')]) }}">

                        <button type="button" class="btn btn-primary" @click="copy()">
                            <span x-show="! copied">{{ __('Copy link') }}</span>
                            <span x-show="copied" x-cloak>{{ __('Copied') }}</span>
                        </button>
                    </div>

                    <p class="text-xs" style="color: var(--text-low)">
                        {{ __('Shown only now — once you leave this page it cannot be retrieved, only sent again.') }}
                    </p>
                </div>
            @endif

            <div class="card overflow-x-auto">
                <table class="table-royal text-sm min-w-[760px]">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Email') }}</th>
                            <th>{{ __('Age') }}</th>
                            <th>{{ __('Parents') }}</th>
                            <th>{{ __('Account') }}</th>
                            <th class="text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($people as $person)
                            <tr>
                                <td >
                                    <a href="{{ route('people.show', $person) }}" class="font-medium text-content-hi hover:underline">
                                        {{ $person->full_name }}
                                    </a>
                                    @if ($person->user?->managesTree())
                                        <span class="privacy-badge-everyone ml-1">{{ __('Super Admin') }}</span>
                                    @endif
                                </td>
                                <td style="color: var(--text-mid)">{{ $person->email }}</td>
                                <td >
                                    <span class="numeric">{{ $person->date_of_birth->age }}</span> {{ __('yrs') }}
                                    @if ($person->isMinor())
                                        <span class="privacy-badge-family">{{ __('Minor') }}</span>
                                    @else
                                        <span class="privacy-badge-everyone">{{ __('Adult') }}</span>
                                    @endif
                                </td>
                                <td style="color: var(--text-mid)">
                                    {{ $person->parents->pluck('full_name')->implode(', ') ?: '—' }}
                                </td>
                                <td >
                                    @if ($person->isClaimed())
                                        <span class="privacy-badge-everyone">{{ __('Claimed') }}</span>
                                    @elseif ($person->claim_status === 'pending_invite')
                                        <span class="privacy-badge-family">{{ __('Invite pending') }}</span>
                                    @else
                                        <span class="privacy-badge-private">{{ __('No login') }}</span>
                                    @endif
                                </td>
                                <td >
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('people.edit', $person) }}" class="btn btn-secondary text-xs px-3 py-1.5">{{ __('Edit') }}</a>

                                        @if ($person->isClaimed())
                                            <a href="{{ route('admin.people.password.edit', $person) }}" class="btn btn-secondary text-xs px-3 py-1.5">{{ __('Reset password') }}</a>
                                        @elseif (! $person->user?->managesTree())
                                            <form method="POST" action="{{ route('admin.people.resend-invite', $person) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary text-xs px-3 py-1.5">
                                                    {{ $person->claim_status === 'pending_invite' ? __('Resend invitation') : __('Send invitation') }}
                                                </button>
                                            </form>
                                        @endif

                                        @unless ($person->user?->managesTree())
                                            <form method="POST" action="{{ route('admin.people.destroy', $person) }}"
                                                  onsubmit="return confirm('{{ __('Remove :name from the family tree? This cannot be undone.', ['name' => $person->full_name]) }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger text-xs px-3 py-1.5">{{ __('Remove') }}</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
