<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

class CursorDto
{
    private const DEFAULT_VERSION = 1;
    private const DEFAULT_SORT = ['created_at', 'id'];

    public function __construct(
        public int $v = self::DEFAULT_VERSION,
        public string $dir = CursorDirection::NEXT->value,
        public array $filters = [],
        public array $sort = self::DEFAULT_SORT,
        public ?array $pos = null,
        public ?array $hwm = null,
        public ?int $exp = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            v: $data['v'] ?? self::DEFAULT_VERSION,
            dir: $data['dir'] ?? CursorDirection::NEXT->value,
            filters: $data['filters'] ?? [],
            sort: $data['sort'] ?? self::DEFAULT_SORT,
            pos: $data['pos'] ?? null,
            hwm: $data['hwm'] ?? null,
            exp: $data['exp'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'v' => $this->v,
            'dir' => $this->dir,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'pos' => $this->pos,
            'hwm' => $this->hwm,
            'exp' => $this->exp,
        ];
    }
}
