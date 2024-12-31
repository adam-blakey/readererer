<div class="mt-1 row row-cards">
    <div class="col-12">
        <div class="card">
            <div class="card-body align-right">
                <nav aria-label="{{ __('Pagination Navigation') }}" role="navigation">
                    @if ($paginator->hasPages())
                        <ul class="pagination">
                            {{-- Previous Page Link --}}
                            @if ($paginator->onFirstPage())
                                <li class="page-item disabled" aria-disabled="true">
                                    <span class="page-link" tabindex="-1" aria-disabled="true">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M15 6l-6 6l6 6"></path>
                                        </svg>
                                        prev
                                    </span>
                                </li>
                            @else
                                <li class="page-item" rel="prev" aria-disabled="true">
                                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" tabindex="-1">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M15 6l-6 6l6 6"></path>
                                        </svg>
                                        prev
                                    </a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @foreach ($elements as $element)
                                {{-- "Three Dots" Separator --}}
                                @if (is_string($element))
                                    <li class="page-item disabled" aria-disabled="true">
                                        <span class="page-link">{{ $element }}</span>
                                    </li>
                                @endif

                                {{-- Array Of Links --}}
                                @if (is_array($element))
                                    @foreach ($element as $page => $url)
                                        @if ($page == $paginator->currentPage())
                                            <li class="page-item" aria-current="page">
                                                <a class="page-link active">{{ $page }}</a>
                                            </li>
                                        @else
                                            <li class="page-item"
                                                aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                            </li>
                                        @endif
                                    @endforeach
                                @endif
                            @endforeach

                            {{-- Next Page Link --}}
                            @if ($paginator->hasMorePages())
                                <li class="page-item" aria-label="{{ __('pagination.next') }}" rel="next">
                                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}">
                                        next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M9 6l6 6l-6 6"></path>
                                        </svg>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled" aria-disabled="true">
                                    <span class="page-link">
                                        next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" class="icon">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                            <path d="M9 6l6 6l-6 6"></path>
                                        </svg>
                                    </span>
                                </li>
                            @endif
                        </ul>
                    @else
                        <ul class="pagination">
                            <li class="page-item disabled" aria-disabled="true">
                                <span class="page-link" tabindex="-1" aria-disabled="true">
                                    <!-- Download SVG icon from http://tabler-icons.io/i/chevron-left -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M15 6l-6 6l6 6"></path>
                                    </svg>
                                    prev
                                </span>
                            </li>

                            <li class="page-item" aria-current="page">
                                <a class="page-link active">1</a>
                            </li>

                            <li class="page-item disabled" aria-disabled="true">
                                <span class="page-link">
                                    next <!-- Download SVG icon from http://tabler-icons.io/i/chevron-right -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round" class="icon">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M9 6l6 6l-6 6"></path>
                                    </svg>
                                </span>
                            </li>
                        </ul>
                    @endif
                </nav>
            </div>
        </div>
    </div>
</div>
