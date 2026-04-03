@props(['entity', 'page_name', 'page_subname', 'edit_route', 'destroy_route', 'restore_route'])

@php
    $attributes = $entity->getVisible();
@endphp

<x-layout :$page_name :$page_subname>
    <div class="container-xl">
        <x-card-row>
            <div class="col-md-12">
                <x-card>
                    <div class="card-header">
                        <h3 class="card-title">
                            {{ $page_subname }}
                        </h3>
                        <div class="card-actions">
                            @if ($edit_route)
                                <x-a :route="$edit_route" :model="$entity" class="btn">
                                    Edit
                                </x-a>
                            @endif
{{--                            TODO: Button alignment --}}
                            @if ($entity->deleted_at != null)
                                @if ($restore_route)
                                    <form method="POST" action="{{ route($restore_route, $entity) }}" onsubmit="return confirm('Are you sure you want to unarchive this record?');">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-success">Unarchive</button>
                                    </form>
                                @endif
                            @else
                                @if ($destroy_route)
                                    <form method="POST" action="{{ route($destroy_route, $entity) }}" onsubmit="return confirm('Are you sure you want to archive this record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Archive</button>
                                    </form>
                                @endif
                            @endif
                        </div>
                    </div>

                    <div class="card-body">
                        @foreach($attributes as $attribute)
                            @php
                                $clean_attribute = clean_attribute_name($attribute);
                                $icon_name = $entity->getIconForAttribute($attribute) ?? 'pencil';
                            @endphp
                            <div class="mb-2">
                                <x-icon name="{{ $icon_name }}" />
                                {{ $clean_attribute }}:
                                @if ($entity->$attribute instanceof Illuminate\Support\Collection)
                                    @if ($entity->$attribute->count() == 0)
                                        -
                                    @else
                                        @foreach ($entity->$attribute as $item)
                                            @if (class_exists(get_class($item)))
                                                <x-clickable-model :model="$item" />
                                            @else
                                                {{ $item }}
                                            @endif
                                            {{ $loop->last ? '' : ',' }}
                                        @endforeach
                                    @endif
                                @else
                                    <strong>{{ $entity->$attribute }}</strong>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-card>
            </div>
        </x-card-row>
    </div>
</x-layout>
