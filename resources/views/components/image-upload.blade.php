@props(['name', 'currentUrl' => null])

{{--
    Choosing a file opens the crop/zoom/rotate editor before anything is
    submitted; the thumbnail here shows the finished result in the same
    proportions the family tree uses.
--}}
<div data-photo-upload class="flex items-center gap-4">
    <img data-photo-preview
         src="{{ $currentUrl }}"
         @unless ($currentUrl) hidden @endunless
         alt=""
         class="object-cover shrink-0"
         style="width: 72px; height: 82px; border-radius: 10px; border: 2px solid var(--gold-500)">

    <div>
        <label class="btn btn-secondary cursor-pointer">
            <span>{{ $currentUrl ? __('Change photo') : __('Choose photo') }}</span>
            <input
                type="file"
                name="{{ $name }}"
                accept="image/*"
                class="sr-only"
            >
        </label>
        <p class="text-xs mt-1.5" style="color: var(--text-low)">
            {{ __('You can crop, zoom and rotate it next.') }}
        </p>
    </div>
</div>
