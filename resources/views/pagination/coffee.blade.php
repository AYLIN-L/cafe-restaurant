@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}"
         class="flex flex-col items-center justify-between gap-4 sm:flex-row">

        <p class="text-[13px] text-coffee/55 sm:text-sm">
            @if ($paginator->firstItem())
                Affichage
                <span class="font-medium text-coffee">{{ $paginator->firstItem() }}</span>
                à
                <span class="font-medium text-coffee">{{ $paginator->lastItem() }}</span>
                sur
                <span class="font-medium text-coffee">{{ $paginator->total() }}</span>
            @else
                {{ $paginator->count() }} résultat{{ $paginator->count() > 1 ? 's' : '' }}
            @endif
        </p>

        <div class="flex items-center gap-1.5">

            {{-- Précédent --}}
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-coffee/10 text-coffee/30">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-coffee/10 bg-white text-coffee shadow-sm transition duration-200 hover:border-amber/60 hover:bg-cream/60 hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @endif

            {{-- Pages --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true"
                          class="inline-flex h-9 min-w-9 items-center justify-center rounded-full px-2 text-sm text-coffee/40">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page"
                                  class="inline-flex h-9 min-w-9 items-center justify-center rounded-full bg-coffee px-3 text-sm font-medium text-cream shadow-sm">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                               class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-coffee/10 bg-white px-3 text-sm text-coffee shadow-sm transition duration-200 hover:border-amber/60 hover:bg-cream/60 hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Suivant --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                   class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-coffee/10 bg-white text-coffee shadow-sm transition duration-200 hover:border-amber/60 hover:bg-cream/60 hover:text-amber-dark focus:outline-none focus-visible:ring-2 focus-visible:ring-amber/40">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}"
                      class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-coffee/10 text-coffee/30">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif

        </div>
    </nav>
@endif
