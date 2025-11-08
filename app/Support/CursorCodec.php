<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class CursorCodec
{
    public static function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to JSON-encode cursor payload');
        }

        return self::base64UrlEncode($json);
    }

    public static function decode(?string $token): array
    {
        if ($token === null || $token === '') {
            return [];
        }

        $decoded = self::base64UrlDecode($token);
        if ($decoded === null) {
            return [];
        }

        $data = json_decode($decoded, true);
        if (!is_array($data)) {
            return [];
        }

        return $data;
    }

    private static function base64UrlEncode(string $value): string
    {
        $b64 = base64_encode($value);

        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $value): ?string
    {
        $remainder = strlen($value) % 4;
        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $b64 = strtr($value, '-_', '+/');
        $decoded = base64_decode($b64, true);

        return $decoded === false ? null : $decoded;
    }
}
