<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Pagination\Cursor\CursorAdapter;
use App\Pagination\Cursor\CursorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UsersDemoController extends Controller
{
    public function offset(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);

        $paginator = DB::table('users')
            ->orderBy('created_at')
            ->orderBy('id')
            ->simplePaginate(
                perPage: $limit,
                page: $page,
            );

        return response()->json([
            'limit' => $limit,
            'page' => $page,
            'items' => $paginator->items(),
        ]);
    }

    public function cursor(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $next = $request->input('next');
        $prev = $request->input('prev');

        $token = $next ?? $prev;
        $dir = $prev ? 'prev' : 'next';
        $sort = ['created_at', 'id'];

        $dto = $token
            ? CursorFactory::fromToken(token: $token)
            : CursorFactory::fromParams(sort: $sort, dir: $dir);

        $query = DB::table('users');
        foreach ($sort as $column) {
            $query->orderBy($column);
        }

        if ($dto->hwm !== null) {
            foreach ($dto->hwm as $column => $value) {
                $query->where($column, '<=', $value);
            }
        }

        $paginate = $query->cursorPaginate(
            perPage: $limit,
            cursor: CursorAdapter::makePaginate(dto: $dto)
        );

        return response()->json([
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                sort: $sort,
                nextCursor: $paginate->nextCursor(),
                hwm: ['created_at' => Carbon::now()],
            ),
            'prev' => CursorAdapter::makePrev(
                sort: $sort,
                prevCursor: $paginate->previousCursor(),
                hwm: ['created_at' => Carbon::now()],
            ),
            'items' => $paginate->items(),
        ]);
    }


    public function hybrid(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);
        $next = $request->input('next');
        $prev = $request->input('prev');

        $token = $next ?? $prev;
        $dir = $next ? 'next' : ($prev ? 'prev' : null);
        $sort = ['created_at', 'id'];

        $query = DB::table('users');
        foreach ($sort as $column) {
            $query->orderBy($column);
        }

        if ($token === null) {
            $data = $query->simplePaginate(
                perPage: $limit,
                page: $page,
            );

            $items = $data->items();
            if ($data->hasMorePages()) {
                $dto = CursorFactory::fromItems(
                    items: $items,
                    sort: $sort,
                    dir: 'next',
                );
                $nextCursor = CursorAdapter::makeNext(
                    sort: $sort,
                    nextCursor: CursorAdapter::makePaginate(dto: $dto),
                    hwm: ['created_at' => Carbon::now()],
                );
            }

            return response()->json([
                'limit' => $limit,
                'next' => $nextCursor ?? null,
                'prev' => null,
                'items' => $items,
            ]);
        }

        $dto = CursorFactory::fromToken(token: $token);

        if ($dto->hwm !== null) {
            foreach ($dto->hwm as $column => $value) {
                $query->where($column, '<=', $value);
            }
        }

        $paginate = $query->cursorPaginate(
            perPage: $limit,
            cursor: CursorAdapter::makePaginate(dto: $dto)
        );

        return response()->json([
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                sort: $sort,
                nextCursor: $paginate->nextCursor(),
                hwm: ['created_at' => Carbon::now()],
            ),
            'prev' => CursorAdapter::makePrev(
                sort: $sort,
                prevCursor: $paginate->previousCursor(),
                hwm: ['created_at' => Carbon::now()],
            ),
            'items' => $paginate->items(),
        ]);
    }
}
