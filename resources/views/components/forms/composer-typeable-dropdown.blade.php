@php use App\Models\Composer; @endphp
@props(['options', 'name', 'title', 'current_composer_id', 'required' => false])

@php
    $composers = Composer::all()->sortBy(['last_name', 'first_name']);
@endphp

<div class="mb-3">
    <label class="form-label {{ $required?'required':'' }}">{{ $title }}</label>
    <select class="form-select" id="{{ $name }}" name="{{ $name }}" {{ $required?'required':'' }}>

        @foreach($composers as $composer)
            <option value="{{ $composer->id }}" data-custom-properties="<span class=&quot;avatar avatar-xs&quot;>{{ $composer->last_name[0] }}</span>" {{ ($current_composer_id == $composer->id)?'selected':'' }}>
                {{ $composer->full_name($reverse = true) }}
            </option>
        @endforeach
    </select>
</div>

@push('scripts')
    <script type='module'>
        document.addEventListener("DOMContentLoaded", function (event) {
            new TomSelect('#{{ $name }}', {
                copyClassesToDropdown: false,
                dropdownParent: 'body',
                controlInput: '<input>',
                render:{
                    item: function(data,escape) {
                        if( data.customProperties ){
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }
                        return '<div>' + escape(data.text) + '</div>';
                    },
                    option: function(data,escape){
                        if( data.customProperties ){
                            return '<div><span class="dropdown-item-indicator">' + data.customProperties + '</span>' + escape(data.text) + '</div>';
                        }
                        return '<div>' + escape(data.text) + '</div>';
                    },
                },
            });
        });
    </script>
@endpush
