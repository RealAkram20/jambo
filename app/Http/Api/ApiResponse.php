<?php

namespace App\Http\Api;

use Illuminate\Http\JsonResponse;

/**
 * The one envelope every /api/v1 endpoint answers in.
 *
 *   success: {"success": true,  "message": "...", "data": {...}}
 *   failure: {"success": false, "code": "STREAM_LIMIT", "message": "...", "errors": {}}
 *
 * A client that can parse one response can parse all of them, which matters
 * more on a phone than it does in a browser: the app has no devtools, no
 * network tab and no way to ship a fix in an hour.
 *
 * `data` is always an object or a list, never a bare scalar, so adding a field
 * later is additive rather than a breaking change.
 */
final class ApiResponse
{
    public static function ok(mixed $data = null, string $message = 'OK', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? new \stdClass(),
        ], $status);
    }

    public static function created(mixed $data = null, string $message = 'Created'): JsonResponse
    {
        return self::ok($data, $message, 201);
    }

    /** 204 carries no body at all, so there is no envelope to build. */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    /**
     * @param  array<string, array<int, string>>  $errors  Field-keyed, as Laravel produces.
     * @param  int|null  $status  Overrides the code's own status; used only where
     *                            an HTTP contract already exists, such as 429.
     * @param  array<string, mixed>  $extra  Additional top-level keys a specific
     *                            failure needs the client to act on — a 2FA
     *                            challenge token, the tier an upgrade requires.
     *                            Never used for anything the envelope already
     *                            has a place for.
     */
    public static function error(
        ApiErrorCode $code,
        string $message,
        array $errors = [],
        ?int $status = null,
        array $extra = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'code' => $code->value,
            'message' => $message,
            'errors' => (object) $errors,
            ...$extra,
        ], $status ?? $code->status());
    }
}
