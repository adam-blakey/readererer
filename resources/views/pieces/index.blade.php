@props(['pieces'])

<x-layout page_name="Home">
    <div class="space-y-2">
        @foreach($pieces as $piece)
            <x-pieces.card />
        @endforeach
    </div>
</x-layout>
