@props(['ensembles', 'page_name'])

<x-layout :$page_name>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <x-ensembles.table :$ensembles />
                </x-card>
            </div>
        </x-card-row>
        {{ $ensembles->links() }}
    </div>
</x-layout>
