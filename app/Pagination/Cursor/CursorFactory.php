<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

use App\Support\CursorCodec;
use Carbon\CarbonImmutable;

final class CursorFactory
{
    private const DEFAULT_TTL = 30;

    public function fromItems(
        array $items,
        array $sort,
        CursorDirection $dir,
        array $filters = [],
        array $hwm = null,
        int $ttlMinutes = self::DEFAULT_TTL,
    ): CursorDto {
        if (empty($items)) {
            return new CursorDto(
                dir: $dir->value,
                filters: $filters,
                sort: $sort,
                hwm: $hwm,
                exp: CarbonImmutable::now()
                    ->addMinutes($ttlMinutes)
                    ->getTimestamp(),
            );
        }

        $boundaryItem = $dir === CursorDirection::PREV
            ? $items[array_key_first($items)]
            : $items[array_key_last($items)];

        $pos = [];
        foreach ($sort as $column) {
            $pos[$column] = $boundaryItem->$column;
        }

        return new CursorDto(
            dir: $dir->value,
            filters: $filters,
            sort: $sort,
            pos: $pos,
            hwm: $hwm,
            exp: CarbonImmutable::now()
                ->addMinutes($ttlMinutes)
                ->getTimestamp(),
        );
    }

    public function fromToken(
        string $token,
    ): CursorDto {
        $data = CursorCodec::decode($token);
        return CursorDto::fromArray($data);
    }

    public function fromParams(
        array $sort,
        string $dir,
        array $pos = null,
        array $hwm = null,
        array $filters = [],
        int $ttlMinutes = self::DEFAULT_TTL,
    ): CursorDto {
        return new CursorDto(
            dir: $dir,
            filters: $filters,
            sort: $sort,
            pos: $pos,
            hwm: $hwm,
            exp: CarbonImmutable::now()
                ->addMinutes($ttlMinutes)
                ->getTimestamp(),
        );
    }

}
