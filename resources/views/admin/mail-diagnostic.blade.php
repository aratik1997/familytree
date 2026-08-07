<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="eyebrow">{{ __('Admin') }}</p>
            <h2 class="text-2xl mt-1">{{ __('Email check') }}</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            @if (session('mail_sent'))
                <div class="card p-4 text-sm" style="color: var(--success)">
                    {{ __('The mail server accepted a message for :email. If it does not arrive, check the spam folder.', ['email' => session('mail_sent')]) }}
                </div>
            @endif

            @if (session('mail_error'))
                <div class="card p-4 space-y-2">
                    <p class="text-sm font-medium" style="color: var(--danger)">{{ __('The message could not be sent.') }}</p>
                    <p class="text-xs numeric" style="color: var(--text-mid)">{{ session('mail_error') }}</p>
                </div>
            @endif

            {{-- The setting that decides whether anything is delivered at all. --}}
            @if ($settings['transport'] === 'log')
                <div class="card p-4 space-y-1" style="border-color: var(--warning)">
                    <p class="text-sm font-medium" style="color: var(--warning)">
                        {{ __('Email is switched off.') }}
                    </p>
                    <p class="text-sm measure" style="color: var(--text-mid)">
                        {{ __('MAIL_MAILER is set to "log", so messages are written to a file and delivered to nobody — while the app still reports that it sent them. Set MAIL_MAILER=smtp in .env.') }}
                        @if ($settings['log_level'] === 'error')
                            {{ __('They are not even being written, because LOG_LEVEL is "error" and mail is logged at "debug".') }}
                        @endif
                    </p>
                </div>
            @endif

            <div class="card p-6 space-y-4">
                <h3 class="font-serif text-xl">{{ __('What the site is using right now') }}</h3>

                <dl class="divide-y divide-gold-light/20 text-sm">
                    @foreach ([
                        'Mailer' => $settings['mailer'].' ('.$settings['transport'].')',
                        'Host' => $settings['host'] ?: '—',
                        'Port' => $settings['port'] ?: '—',
                        'Scheme' => $settings['scheme'] ?: '—',
                        'Username' => $settings['username'] ?: '—',
                        'From address' => $settings['from_address'],
                    ] as $label => $value)
                        <div class="py-2.5 flex justify-between gap-4">
                            <dt style="color: var(--text-low)">{{ __($label) }}</dt>
                            <dd class="text-right numeric">{{ $value }}</dd>
                        </div>
                    @endforeach

                    <div class="py-2.5 flex justify-between gap-4">
                        <dt style="color: var(--text-low)">{{ __('Password') }}</dt>
                        <dd class="text-right numeric">
                            @if ($settings['password_set'])
                                {{ str_repeat('•', 8) }} ({{ $settings['password_length'] }} {{ __('chars') }})
                            @else
                                <span style="color: var(--danger)">{{ __('not set') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div class="py-2.5 flex justify-between gap-4">
                        <dt style="color: var(--text-low)">{{ __('Config cache') }}</dt>
                        <dd class="text-right">
                            @if ($settings['config_cached'])
                                <span style="color: var(--warning)">{{ __('cached — .env changes will not apply until it is rebuilt') }}</span>
                            @else
                                {{ __('none — .env is read directly') }}
                            @endif
                        </dd>
                    </div>
                </dl>

                @if ($settings['username'] && strcasecmp($settings['username'], $settings['from_address']) !== 0)
                    <p class="text-sm hairline p-3" style="border-radius: var(--radius-control); color: var(--warning)">
                        {{ __('The From address is not the same as the login. Most providers reject that, usually without a clear error.') }}
                    </p>
                @endif
            </div>

            <div class="card p-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="font-medium">{{ __('Send a test message') }}</p>
                    <p class="text-sm mt-1" style="color: var(--text-mid)">
                        {{ __('Goes to your own address, :email, and reports the exact error if it fails.', ['email' => auth()->user()->email]) }}
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.mail-check.send') }}">
                    @csrf
                    <x-primary-button>{{ __('Send test email') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
