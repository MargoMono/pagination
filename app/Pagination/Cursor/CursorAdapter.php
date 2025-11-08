<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

use App\Support\CursorCodec;
use Illuminate\Pagination\Cursor;

final class CursorAdapter
{
    public static function makePaginate(CursorDto $dto): Cursor|null
    {
        if ($dto->pos === null) {
            return null;
        }

        return new Cursor(
            parameters: $dto->pos,
            pointsToNextItems: $dto->dir === 'next',
        );
    }

    public static function makeNext(
        array $sort,
        Cursor|null $nextCursor,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if ($nextCursor === null) {
            return null;
        }
        $next = CursorFactory::fromParams(
            sort: $sort,
            dir: 'next',
            pos: self::getPos(sort: $sort, cursor: $nextCursor),
            hwm: $hwm,
            filters: $filters,
        );

        return CursorCodec::encode($next->toArray());
    }

    public static function makePrev(
        array $sort,
        Cursor|null $prevCursor,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if ($prevCursor === null) {
            return null;
        }
        $prev = CursorFactory::fromParams(
            sort: $sort,
            dir: 'prev',
            pos: self::getPos(sort: $sort, cursor: $prevCursor),
            hwm: $hwm,
            filters: $filters,
        );

        return CursorCodec::encode($prev->toArray());
    }

    private static function getPos(
        array $sort,
        Cursor $cursor,
    ) {
        return array_reduce(
            $sort,
            static function (array $carry, string $col) use ($cursor): array {
                $column = ltrim($col, '-');
                $carry[$column] = $cursor->parameter($column);

                return $carry;
            },
            [],
        );
    }
}
