@props(['piece'])

<x-panel class="flex gap-x-6">
    <div>
        <x-pieces.thumbnail />
    </div>

    <div class="flex-1 flex flex-col">
        <a href="#" class="self-start text-gray-400">Name</a>

        <a class="font-bold text-xl mt-3 group-hover:text-blue-700 transition-colors duration-300" href="#" target="_blank">
            Test
        </a>
        <p class="text-sm text-gray-400 mt-auto">Wow</p>
    </div>

    <div>
        Tags
    </div>
</x-panel>
