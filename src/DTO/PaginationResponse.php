<?php
declare(strict_types=1);

namespace MCAG\DTO;

class PaginationResponse
{
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
        public readonly int $totalPages,
        public readonly bool $hasPrevious,
        public readonly bool $hasNext
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'pagination' => [
                'total' => $this->total,
                'page' => $this->page,
                'per_page' => $this->perPage,
                'total_pages' => $this->totalPages,
                'has_previous' => $this->hasPrevious,
                'has_next' => $this->hasNext
            ]
        ];
    }
}


