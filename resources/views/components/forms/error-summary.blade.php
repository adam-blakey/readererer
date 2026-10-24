@props(['fields' => []])

@php
    // Every other error is already rendered against its own field; this block
    // exists for the ones that have no field to sit next to, which would
    // otherwise bounce the user back to a form that looks perfectly fine.
    $messages = unhandled_error_messages($errors ?? null, (array) $fields);
@endphp

@if (filled($messages))
    <div class="alert alert-danger" role="alert">
        <h4 class="alert-title">{{ trans_choice('There is a problem with this form|There are problems with this form', count($messages)) }}</h4>
        <ul class="mb-0">
            @foreach ($messages as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
