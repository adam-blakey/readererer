@props(['update', 'fields', 'form_route'])

<x-layout>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                    </div>
                    <form action="{{ $form_route }}" method="POST" class="space-y" @if ($update) data-dirty-check @endif>
                        @csrf
                        @if ($update)
                            @method('PATCH')
                        @else
                            @method('POST')
                        @endif
                        <div class="card-body">
                            <x-forms.error-summary :fields="array_keys($fields)" />

                            <div class="space-y">
                                <div class="row">
                                    @foreach($fields as $name => $data)
                                        <x-forms.field :name="$name" :data="$data" />
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">{{ $update ? __('Update') : __('Create') }}</button>
                        </div>
                    </form>
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
