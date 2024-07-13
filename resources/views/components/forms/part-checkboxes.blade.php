@php use App\Models\Part; @endphp

@props(['options', 'name', 'title', 'piece', 'required' => false])

@php
    $parts = Part::all();
@endphp

<div class="mb-3">
    <div class="form-label">{{ $title }}</div>
    <ul>
        @foreach($parts as $part)
            <li class="inline-list" style="width: 12em;">
                <label class="form-check">
                    <input type="checkbox" class="form-check-input" id="{{ $name . '.' . $part->id }}" {{ ($piece->parts->contains($part->id))?'checked':'' }} />
                    <span class="form-check-label">{{ $part->name }}</span>
                </label>
            </li>
        @endforeach
    </ul>
</div>
