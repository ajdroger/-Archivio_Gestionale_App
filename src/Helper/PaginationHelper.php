<?php
declare(strict_types=1);

namespace MCAG\Helper;

class PaginationHelper
{
    public static function paginate(
        int $totalItems,
        int $currentPage = 1,
        int $perPage = 50
    ): array {
        $totalPages = (int) ceil($totalItems / $perPage);
        $currentPage = max(1, min($currentPage, $totalPages));
        $offset = ($currentPage - 1) * $perPage;

        return [
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'offset' => $offset,
            'has_previous' => $currentPage > 1,
            'has_next' => $currentPage < $totalPages,
            'previous_page' => $currentPage > 1 ? $currentPage - 1 : null,
            'next_page' => $currentPage < $totalPages ? $currentPage + 1 : null,
        ];
    }
}


