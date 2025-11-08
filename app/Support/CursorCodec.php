<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Crypt;
use RuntimeException;

final class CursorCodec
{
    public static function encode(array $payload): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            throw new RuntimeException('Failed to JSON-encode cursor payload');
        }

        return Crypt::encryptString($json);
    }

    public static function decode(?string $token): array
    {
        if ($token === null || $token === '') {
            return [];
        }

        $json = Crypt::decryptString($token);
        $data = json_decode($json, true);

        return is_array($data) ? $data : [];
    }
}
