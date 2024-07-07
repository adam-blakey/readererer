@php
    $classes = 'p-4 bg-gray-800 rounded-xl border border-gray-800 text-white';
@endphp

<div {{ $attributes(['class' => $classes]) }}>
    {{ $slot }}
</div>
