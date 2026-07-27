@props(['active'])

@php
$base = 'block w-full ps-3 pe-4 py-3 border-l-4 text-start text-base font-medium transition duration-micro ease-royal';
$classes = ($active ?? false)
    ? $base.' nav-link-active-mobile'
    : $base.' nav-link-idle-mobile';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
