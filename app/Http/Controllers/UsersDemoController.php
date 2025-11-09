<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Pagination\Cursor\CursorAdapter;
use App\Pagination\Cursor\CursorDirection;
use App\Pagination\Cursor\CursorFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UsersDemoController extends Controller
{
    /** @var string[] */
    private array $sort = ['created_at', 'id'];

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
        $direction = CursorDirection::fromRequest($next, $prev);

        $dto = $token
            ? CursorFactory::fromToken(token: $token)
            : CursorFactory::fromParams(sort: $this->sort, dir: $direction->value);

        $query = $this->buildBaseQuery($direction);

        if ($dto->hwm !== null) {
            foreach ($dto->hwm as $column => $value) {
                $query->where($column, '<=', $value);
            }
        }

        if ($dto->pos !== null) {
            $query->whereRowValues(
                $this->sort,
                $direction->operator(),
                [$dto->pos['created_at'], $dto->pos['id']],
            );
        }

        Log::info(
            $query->limit($limit)->toSql(),
            [
                'created_at' => $dto->pos['created_at'] ?? null,
                'id' => $dto->pos['id'] ?? null,
            ]
        );

        $items = $query->limit($limit)->get()->all();

        if ($direction->isPrev()) {
            $items = array_reverse($items);
        }

        return response()->json(
            $this->buildCursorResponse(
                limit: $limit,
                items: $items,
            )
        );
    }

    public function hybrid(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);
        $next = $request->input('next');
        $prev = $request->input('prev');

        $token = $next ?? $prev;
        $direction = CursorDirection::fromRequest($next, $prev);

        $query = $this->buildBaseQuery($direction);

        if ($token === null) {
            $data = $query->simplePaginate(perPage: $limit, page: $page);
            $items = $data->items();

            $nextCursor = $data->hasMorePages()
                ? CursorAdapter::makeNext(
                    items: $items,
                    sort: $this->sort,
                    hwm: ['created_at' => Carbon::now()],
                )
                : null;

            return response()->json([
                'limit' => $limit,
                'next' => $nextCursor,
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
                $this->sort,
                $direction->operator(),
                [$dto->pos['created_at'], $dto->pos['id']],
            );
        }

        Log::info(
            $query->limit($limit)->toSql(),
            [
                'created_at' => $dto->pos['created_at'] ?? null,
                'id' => $dto->pos['id'] ?? null,
            ]
        );

        $items = $query->limit($limit)->get()->all();

        if ($direction->isPrev()) {
            $items = array_reverse($items);
        }

        return response()->json(
            $this->buildCursorResponse(
                limit: $limit,
                items: $items,
            )
        );
    }

    private function buildBaseQuery(CursorDirection $direction): Builder
    {
        $query = DB::table('users');

        foreach ($this->sort as $column) {
            $query->orderBy($column, $direction->order());
        }

        return $query;
    }

    private function buildCursorResponse(int $limit, array $items): array
    {
        $hwm = ['created_at' => Carbon::now()];

        return [
            'limit' => $limit,
            'next' => CursorAdapter::makeNext(
                items: $items,
                sort: $this->sort,
                hwm: $hwm,
            ),
            'prev' => CursorAdapter::makePrev(
                items: $items,
                sort: $this->sort,
                hwm: $hwm,
            ),
            'items' => $items,
        ];
    }
}
