@props(['name', 'selected' => 'family'])

<select name="{{ $name }}" {{ $attributes->merge(['class' => 'field text-xs']) }} style="min-height: 36px; padding: .25rem 1.75rem .25rem .5rem">
    <option value="everyone" @selected($selected === 'everyone')>{{ __('Everyone') }}</option>
    <option value="family" @selected($selected === 'family')>{{ __('Family') }}</option>
    <option value="private" @selected($selected === 'private')>{{ __('Private') }}</option>
</select>
