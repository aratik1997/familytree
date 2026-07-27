@props(['visibility'])

@php
$labels = ['everyone' => 'Everyone', 'family' => 'Family', 'private' => 'Private'];
$classes = [
    'everyone' => 'privacy-badge-everyone',
    'family' => 'privacy-badge-family',
    'private' => 'privacy-badge-private',
];
@endphp

<span {{ $attributes->merge(['class' => $classes[$visibility] ?? $classes['family']]) }}>
    {{ $labels[$visibility] ?? ucfirst($visibility) }}
</span>
