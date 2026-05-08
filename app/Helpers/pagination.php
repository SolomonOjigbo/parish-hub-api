<?php

use Illuminate\Pagination\LengthAwarePaginator;

if (!function_exists('paginator_meta')) {
    /**
     * Extract pagination metadata in the standard API shape.
     */
    function paginator_meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
    }
}
