@props(['page_name', 'pre_title' => ''])

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                @if($pre_title != '')
                    <div class="page-pretitle">
                        {{ $pre_title }}
                    </div>
                @endif
                <h2 class="page-title">
                    {{ $page_name }}
                </h2>
            </div>
        </div>
    </div>
</div>
