<?php

declare(strict_types=1);

namespace App\Pagination\Cursor\Source;

use Illuminate\Database\Query\Builder;

interface CursorSource
{
    public function baseQuery(): Builder;

    public function sortColumns(): array;
}
