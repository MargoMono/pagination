<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

use App\Pagination\Cursor\Source\CursorSource;
use App\Support\CursorCodec;
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
        CursorDirection $direction,
        array $filters = [],
    ): array {
        $sort = $source->sortColumns();

        $dto = $token
            ? $this->cursorFactory->fromToken($token)
            : $this->cursorFactory->fromParams(
                sort: $sort,
                dir: $direction->value,
                filters: $filters,
            );

        $query = $source->baseQuery();
        $source->applyFilters($query, $filters);

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

        return [
            'limit' => $limit,
            'next' => $this->makeNext(
                items: $items,
                sort: $sort,
                hwm: $hwm,
                filters: $dto->filters,
            ),
            'prev' => $token ? $this->makePrev(
                items: $items,
                sort: $sort,
                hwm: $hwm,
                filters: $dto->filters,
            ) : null,
            'items' => $items,
        ];
    }

    public function hybridPage(
        CursorSource $source,
        int $limit,
        int $page,
        ?string $token,
        CursorDirection $direction,
        array $filters = [],
    ): array {
        $sort = $source->sortColumns();

        if ($token === null) {
            $query = $source->baseQuery();
            $source->applyFilters($query, $filters);

            foreach ($sort as $column) {
                $query->orderBy($column);
            }

            $paginator = $query->simplePaginate(
                perPage: $limit,
                page: $page,
            );

            $items = $paginator->items();

            $hwm = ['created_at' => CarbonImmutable::now()];

            return [
                'limit' => $limit,
                'next' => $paginator->hasMorePages()
                    ? $this->makeNext(
                        items: $items,
                        sort: $sort,
                        hwm: $hwm,
                        filters: $filters,
                    )
                    : null,
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

    private function makeNext(
        array $items,
        array $sort,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if ($items === []) {
            return null;
        }

        $next = $this->cursorFactory->fromItems(
            items: $items,
            sort: $sort,
            dir: CursorDirection::NEXT,
            filters: $filters,
            hwm: $hwm,
        );

        return CursorCodec::encode($next->toArray());
    }

    private function makePrev(
        array $items,
        array $sort,
        array $hwm = [],
        array $filters = [],
    ): string|null {
        if ($items === []) {
            return null;
        }

        $prev = $this->cursorFactory->fromItems(
            items: $items,
            sort: $sort,
            dir: CursorDirection::PREV,
            filters: $filters,
            hwm: $hwm,
        );

        return CursorCodec::encode($prev->toArray());
    }
}
