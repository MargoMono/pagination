<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

enum CursorDirection: string
{
    case NEXT = 'next';
    case PREV = 'prev';

    public static function fromRequest(?string $next, ?string $prev): self
    {
        return $prev !== null ? self::PREV : self::NEXT;
    }

    public function operator(): string
    {
        return match ($this) {
            self::NEXT => '>',
            self::PREV => '<',
        };
    }

    public function order(): string
    {
        return match ($this) {
            self::NEXT => 'asc',
            self::PREV => 'desc',
        };
    }

    public function isPrev(): bool
    {
        return $this === self::PREV;
    }
}
