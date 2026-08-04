@php
    // Set by PasswordResetLinkController after a link is sent or refused.
    $retryAfter = (int) session('retry_after', 0);
    $retryEmail = session('retry_email');
@endphp

<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow">{{ __('Forgotten password') }}</p>
        <h1 class="text-xl mt-1">{{ __('Get a reset link') }}</h1>
        <p class="text-sm measure mt-1" style="color: var(--text-mid)">
            {{ __('Tell us the email address on your account and we will send you a link to choose a new password.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{--
        The countdown only runs for the address it was issued against: typing
        a different one re-enables the button, since that address has its own
        allowance.
    --}}
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4"
          x-data="{
              remaining: {{ $retryAfter }},
              issuedFor: @js($retryEmail),
              typed: @js(old('email', '')),
              ticker: null,

              get waiting() {
                  return this.remaining > 0
                      && this.issuedFor !== null
                      && this.typed.trim().toLowerCase() === String(this.issuedFor).toLowerCase();
              },

              get clock() {
                  const minutes = Math.floor(this.remaining / 60);
                  const seconds = String(this.remaining % 60).padStart(2, '0');
                  return minutes > 0 ? `${minutes}:${seconds}` : `0:${seconds}`;
              },

              init() {
                  if (this.remaining <= 0) return;
                  this.ticker = setInterval(() => {
                      if (--this.remaining <= 0) clearInterval(this.ticker);
                  }, 1000);
              },
          }">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block w-full" type="email" name="email"
                          x-model="typed" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Announced once when it appears, rather than on every tick. --}}
        <p x-show="waiting" x-cloak
           class="text-sm hairline p-3"
           style="border-radius: var(--radius-control); color: var(--text-mid)"
           aria-live="polite">
            {{ __('A link has just gone out. You can ask for another in') }}
            <span class="numeric font-medium" style="color: var(--gold-text)" x-text="clock"></span>.
        </p>

        <div class="flex items-center justify-between gap-3 pt-2">
            <a href="{{ route('login') }}" class="text-sm underline underline-offset-4"
               style="color: var(--text-mid)">{{ __('Back to sign in') }}</a>

            <x-primary-button x-bind:disabled="waiting" x-bind:aria-disabled="waiting.toString()">
                <span x-show="! waiting">{{ __('Email me a link') }}</span>
                <span x-show="waiting" x-cloak>
                    {{ __('Wait') }} <span class="numeric" x-text="clock"></span>
                </span>
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
