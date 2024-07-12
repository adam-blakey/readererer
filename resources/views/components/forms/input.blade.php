@props(['name', 'title', 'required' => false, 'type' => 'text', 'placeholder' => '', 'hint' => ''])

<div class="mb-3">
    <label class="form-label {{ $required?'required':'' }}">{{ $title }}</label>
    <div>
        <input type="{{ $type }}" class="form-control" aria-describedby="emailHelp" placeholder="{{ $placeholder }}" id="{{ $name }}" name="{{ $name }}" {{ $required?'required':'' }}>
        @if($hint != '')
            <small class="form-hint">{{ $hint }}</small>
        @endif
    </div>
</div>
