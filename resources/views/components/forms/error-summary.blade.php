@props(['fields' => []])

@php($messages = unhandled_error_messages($errors ?? null, $fields))

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
