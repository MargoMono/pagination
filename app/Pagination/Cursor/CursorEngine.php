<?php

namespace App\Pagination\Cursor;

use App\Pagination\Cursor\Source\CursorSource;
use Carbon\CarbonImmutable;

final readonly class CursorEngine
{
    public function __construct(
        private CursorFactory $cursorFactory,
    ) {
    }

    public function cursorPage(
        CursorSource $source,
        int $limit,
        ?string $token,
        CursorDirection $direction
    ): array {
        $sort = $source->sortColumns();

        $dto = $token
            ? $this->cursorFactory->fromToken($token)
            : $this->cursorFactory->fromParams(sort: $sort, dir: $direction->value);

        $query = $source->baseQuery();

        CursorQueryApplier::apply(
            query: $query,
            dto: $dto,
            direction: $direction,
            sort: $sort,
        );

        $items = $query
            ->limit($limit)
            ->get()
            ->all();

        if ($direction->isPrev()) {
            $items = array_reverse($items);
        }

        $hwm = ['created_at' => CarbonImmutable::now()];

        return CursorResponseBuilder::build(
            limit: $limit,
            items: $items,
            sort: $sort,
            hwm: $hwm,
        );
    }

    public function hybridPage(
        CursorSource $source,
        int $limit,
        int $page,
        ?string $token,
        CursorDirection $direction
    ): array {
        $sort = $source->sortColumns();

        if ($token === null) {
            $query = $source->baseQuery();

            foreach ($sort as $column) {
                $query->orderBy($column);
            }

            $paginator = $query->simplePaginate(
                perPage: $limit,
                page: $page,
            );

            $items = $paginator->items();

            $hwm = ['created_at' => CarbonImmutable::now()];

            $nextCursor = $paginator->hasMorePages()
                ? CursorAdapter::makeNext(
                    items: $items,
                    sort: $sort,
                    hwm: $hwm,
                )
                : null;

            return [
                'limit' => $limit,
                'next' => $nextCursor,
                'prev' => null,
                'items' => $items,
            ];
        }

        return $this->cursorPage(
            source: $source,
            limit: $limit,
            token: $token,
            direction: $direction,
        );
    }
}
