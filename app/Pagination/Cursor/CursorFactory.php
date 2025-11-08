<?php

declare(strict_types=1);

namespace App\Pagination\Cursor;

use App\Support\CursorCodec;
use Carbon\CarbonImmutable;

final class CursorFactory
{
    private const DEFAULT_TTL = 30;

    public static function fromItems(
        array $items,
        array $sort,
        string $dir,
        array $filters = [],
        array $hwm = null,
        int $ttlMinutes = self::DEFAULT_TTL,
    ): CursorDto {
        $lastItem = !empty($items)
            ? $items[array_key_last($items)]
            : null;

        $pos = [];
        foreach ($sort as $column) {
            $pos[$column] = $lastItem->$column;
        }

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

    public static function fromToken(
        string $token,
    ): CursorDto {
        $data = CursorCodec::decode($token);
        return CursorDto::fromArray($data);
    }

    public static function fromParams(
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
