@props(['pieces', 'page_name', 'pre_title'])

<x-layout :$page_name :$pre_title>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card :add_card_body="false">
                    <x-pieces.table :$pieces />
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
