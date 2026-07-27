@props(['active'])

@php
$base = 'inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-micro ease-royal';
$classes = ($active ?? false)
    ? $base.' nav-link-active'
    : $base.' nav-link-idle';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
