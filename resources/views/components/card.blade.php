@props(['header' => '', 'add_card_body' => true])

<div class="card">
    @if($header != '')
        <div class="card-header">
            <h3 class="card-title">
                {{ $header }}
            </h3>
        </div>
    @endif

    @if ($add_card_body)
        <div class="card-body">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
