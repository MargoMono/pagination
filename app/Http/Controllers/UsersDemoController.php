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
use Illuminate\Support\Facades\Log;

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

        $operators = [
            'next' => '>',
            'prev' => '<',
        ];
        $orders = [
            'next' => 'asc',
            'prev' => 'desc',
        ];

        $dto = $token
            ? CursorFactory::fromToken(token: $token)
            : CursorFactory::fromParams(sort: $sort, dir: $dir);

        $query = DB::table('users');
        foreach ($sort as $column) {
            $query->orderBy($column, $orders[$dir]);
        }

        if ($dto->hwm !== null) {
            foreach ($dto->hwm as $column => $value) {
                $query->where($column, '<=', $value);
            }
        }

        if ($dto->pos !== null) {
            $query->whereRowValues(
                ['created_at', 'id'],
                $operators[$dir],
                [$dto->pos['created_at'], $dto->pos['id']],
            );
        }

        Log::info($query->limit($limit)->toSql(), [
            'created_at' => $dto->pos ? $dto->pos['created_at'] : null,
            'id' => $dto->pos ? $dto->pos['id'] : null,
        ]);

        $items = $query->limit($limit)->get()->all();

        if ($dir === 'prev') {
            $items = array_reverse($items);
        }

        return response()->json([
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                items: $items,
                sort: $sort,
                hwm: ['created_at' => Carbon::now()],
            ),
            'prev' => CursorAdapter::makePrev(
                items: $items,
                sort: $sort,
                hwm: ['created_at' => Carbon::now()],
            ),
            'items' => $items,
        ]);
    }


    public function hybrid(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);
        $next = $request->input('next');
        $prev = $request->input('prev');

        $token = $next ?? $prev;
        $dir = $prev ? 'prev' : 'next';
        $sort = ['created_at', 'id'];
        $operators = [
            'next' => '>',
            'prev' => '<',
        ];
        $orders = [
            'next' => 'asc',
            'prev' => 'desc',
        ];

        $query = DB::table('users');
        foreach ($sort as $column) {
            $query->orderBy($column, $orders[$dir]);
        }

        if ($token === null) {
            $data = $query->simplePaginate(
                perPage: $limit,
                page: $page,
            );

            $items = $data->items();
            if ($data->hasMorePages()) {
                $nextCursor = CursorAdapter::makeNext(
                    items: $items,
                    sort: $sort,
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

        if ($dto->pos !== null) {
            $query->whereRowValues(
                ['created_at', 'id'],
                $operators[$dir],
                [$dto->pos['created_at'], $dto->pos['id']],
            );
        }

        Log::info($query->limit($limit)->toSql(), [
            'created_at' => $dto->pos['created_at'],
            'id' => $dto->pos['id'],
        ]);

        $items = $query->limit($limit)->get()->all();

        if ($dir === 'prev') {
            $items = array_reverse($items);
        }

        return response()->json([
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                items: $items,
                sort: $sort,
                hwm: ['created_at' => Carbon::now()],
            ),
            'prev' => CursorAdapter::makePrev(
                items: $items,
                sort: $sort,
                hwm: ['created_at' => Carbon::now()],
            ),
            'items' => $items,
        ]);
    }
}
