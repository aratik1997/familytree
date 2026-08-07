{{-- Language switch. Two plain links rather than a dropdown: with only two
     languages, the one you are not using should be one tap away, and it has to
     be recognisable to someone who cannot read the other one. Each is written
     in its own script for that reason — "বাংলা" never appears as "Bengali". --}}
@php($current = app()->getLocale())

<div {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-xs']) }}>
    @foreach (\App\Http\Middleware\SetLocale::SUPPORTED as $code => $label)
        @if ($code === $current)
            <span class="px-2 py-1 rounded"
                  style="color: var(--text-hi); background: color-mix(in srgb, var(--gold-500) 18%, transparent)"
                  aria-current="true">{{ $label }}</span>
        @else
            <a href="{{ route('locale.update', $code) }}"
               class="px-2 py-1 rounded hover:underline"
               style="color: var(--text-mid)">{{ $label }}</a>
        @endif
    @endforeach
</div>
