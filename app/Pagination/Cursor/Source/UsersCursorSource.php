<?php

declare(strict_types=1);

namespace App\Pagination\Cursor\Source;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class UsersCursorSource implements CursorSource
{
    public function baseQuery(): Builder
    {
        return DB::table('users');
    }

    public function sortColumns(): array
    {
        return ['created_at', 'id'];
    }

    public function applyFilters(Builder $query, array $filters): void
    {
        if (isset($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        if (isset($filters['verified'])) {
            $filters['verified']
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }
    }
}
