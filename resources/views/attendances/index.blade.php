@props(['attendances', 'page_name'])

<x-layout :$page_name>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <x-attendances.table :$attendances />
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
