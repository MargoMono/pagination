<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

use App\Support\CursorCodec;

final class CursorAdapter
{

    public static function makeNext(
        array $items,
        array $sort,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if (empty($items)) {
            return null;
        }
        $next = CursorFactory::fromItems(
            items: $items,
            sort: $sort,
            dir: 'next',
            filters: $filters,
            hwm: $hwm,
        );

        return CursorCodec::encode($next->toArray());
    }

    public static function makePrev(
        array $items,
        array $sort,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if (empty($items)) {
            return null;
        }
        $prev = CursorFactory::fromItems(
            items: $items,
            sort: $sort,
            dir: 'prev',
            filters: $filters,
            hwm: $hwm,
        );
        return CursorCodec::encode($prev->toArray());
    }
}
