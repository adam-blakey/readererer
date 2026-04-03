@props(['update', 'fields', 'page_name', 'page_subname', 'form_route'])

<x-layout :$page_name :$page_subname>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                    </div>
                    <form action="{{ $form_route }}" method="POST" class="space-y">
                        @csrf
                        @if ($update)
                            @method('PATCH')
                        @else
                            @method('POST')
                        @endif
                        <div class="card-body">
                            <div class="space-y">
                                <div class="row">
                                    @foreach($fields as $name => $data)
                                        <x-forms.field :name="$name" :data="$data" />
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">{{ $update ? 'Update' : 'Create' }}</button>
                        </div>
                    </form>
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
