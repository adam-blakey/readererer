@props(['terms', 'page_name'])

<x-layout :$page_name>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <x-terms.table :$terms />
                </x-card>
            </div>
        </x-card-row>
        {{ $terms->links() }}
    </div>
</x-layout>
