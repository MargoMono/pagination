<?php

namespace App\Pagination\Cursor;

use Illuminate\Database\Query\Builder;

final class CursorQueryApplier
{
    public static function apply(Builder $query, CursorDto $dto, CursorDirection $direction, array $sort): void
    {
        foreach ($sort as $column) {
            $query->orderBy($column, $direction->order());
        }

        if ($dto->hwm !== null) {
            foreach ($dto->hwm as $column => $value) {
                $query->where($column, '<=', $value);
            }
        }

        if ($dto->pos !== null) {
            $query->whereRowValues(
                $sort,
                $direction->operator(),
                array_values($dto->pos),
            );
        }
    }
}

