@props(['piece', 'page_name', 'pre_title'])

<x-layout :$page_name :$pre_title>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card :header="$pre_title">
                    <form>
                        <x-forms.input type="text" name="name" title="Name" required="true" />
                    </form>
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
