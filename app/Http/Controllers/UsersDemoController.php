<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Pagination\Cursor\CursorAdapter;
use App\Pagination\Cursor\CursorFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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
        $sort = ['created_at', 'id'];

        $dto = $next
            ? CursorFactory::fromNext(next: $next)
            : CursorFactory::fromParams(sort: $sort);

        $q = DB::table('users');
        foreach ($sort as $column) {
            $q->orderBy($column);
        }

        $paginate = $q->cursorPaginate(
            perPage: $limit,
            cursor: CursorAdapter::makePaginate(dto: $dto)
        );

        $nextCursor = CursorAdapter::makeNext(
            sort: $sort,
            nextCursor: $paginate->nextCursor(),
        );

        return response()->json([
            'limit' => $limit,
            'next' => $nextCursor,
            'items' => $paginate->items(),
        ]);
    }


    public function hybrid(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);
        $next = $request->input('next');
        $sort = ['created_at', 'id'];

        $q = DB::table('users');
        foreach ($sort as $column) {
            $q->orderBy($column);
        }

        if ($next === null) {
            $data = $q->simplePaginate(
                perPage: $limit,
                page: $page,
            );

            $items = $data->items();
            if ($data->hasMorePages()) {
                $dto = CursorFactory::fromItems(
                    items: $items,
                    sort: $sort,
                );
                $nextCursor = CursorAdapter::makeNext(
                    sort: $sort,
                    nextCursor: CursorAdapter::makePaginate(dto: $dto),
                );
            }

            return response()->json([
                'limit' => $limit,
                'next' => $nextCursor ?? [],
                'items' => $items,
            ]);
        }

        $dto = CursorFactory::fromNext(next: $next);

        $paginate = $q->cursorPaginate(
            perPage: $limit,
            cursor: CursorAdapter::makePaginate(dto: $dto)
        );

        $nextCursor = CursorAdapter::makeNext(
            sort: $sort,
            nextCursor: $paginate->nextCursor(),
        );

        return response()->json([
            'limit' => $limit,
            'next' => $nextCursor,
            'items' => $paginate->items(),
        ]);
    }
}
