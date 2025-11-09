<?php

namespace App\Pagination\Cursor;

final class CursorResponseBuilder
{
    public static function build(
        int $limit,
        array $items,
        array $sort,
        array $hwm
    ): array {
        return [
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                items: $items,
                sort: $sort,
                hwm: $hwm,
            ),
            'prev' => CursorAdapter::makePrev(
                items: $items,
                sort: $sort,
                hwm: $hwm,
            ),
            'items' => $items,
        ];
    }
}
