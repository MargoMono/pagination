<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Pagination\Cursor\CursorDirection;
use App\Pagination\Cursor\CursorEngine;
use App\Pagination\Cursor\Source\UsersCursorSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class UsersDemoController extends Controller
{
    public function __construct(
        private readonly CursorEngine $engine,
        private readonly UsersCursorSource $usersSource,
    ) {
    }

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
        $direction = CursorDirection::fromRequest(prev: $prev);

        $data = $this->engine->cursorPage(
            source: $this->usersSource,
            limit: $limit,
            token: $token,
            direction: $direction,
        );

        return response()->json($data);
    }

    public function hybrid(Request $request): JsonResponse
    {
        $limit = (int)$request->input('limit', 50);
        $page = (int)$request->input('page', 1);
        $next = $request->input('next');
        $prev = $request->input('prev');

        $token = $next ?? $prev;
        $direction = CursorDirection::fromRequest(prev: $prev);

        $data = $this->engine->hybridPage(
            source: $this->usersSource,
            limit: $limit,
            page: $page,
            token: $token,
            direction: $direction,
        );

        return response()->json($data);
    }
}
