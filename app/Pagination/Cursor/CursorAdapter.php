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
            pointsToNextItems: $dto->dir,
        );
    }


    public static function makeNext(
        array $sort,
        Cursor $nextCursor,
        array $hwm = [],
        array $filters = [],
    ): string {
        $nextPos = array_reduce(
            $sort,
            static function (array $carry, string $col) use ($nextCursor): array {
                $column = ltrim($col, '-');
                $carry[$column] = $nextCursor->parameter($column);

                return $carry;
            },
            [],
        );

        $next = CursorFactory::fromParams(
            sort: $sort,
            pos: $nextPos,
            hwm: $hwm,
            filters: $filters,
        );

        return CursorCodec::encode($next->toArray());
    }
}
