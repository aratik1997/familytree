@props(['name' => 'gender', 'selected' => null, 'id' => 'gender'])

{{-- Gender carries a glyph alongside the label, so the meaning never rests
     on colour alone anywhere it is later rendered. --}}
<select id="{{ $id }}" name="{{ $name }}" {{ $attributes->merge(['class' => 'field mt-1']) }}>
    <option value="" @selected(blank($selected))>{{ __('Select…') }}</option>
    <option value="male" @selected($selected === 'male')>{{ __('♂ Male') }}</option>
    <option value="female" @selected($selected === 'female')>{{ __('♀ Female') }}</option>
</select>
