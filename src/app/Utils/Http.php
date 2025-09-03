<?php
declare(strict_types=1);

namespace App\Utils;

use Phalcon\Http\Response;

final class Http
{
    public static function json(int $code, array $payload): Response
    {
        $res = new Response();
        $res->setStatusCode($code);
        $res->setHeader('Content-Type', 'application/json');
        $res->setJsonContent($payload);
        return $res;
    }

    public static function error(int $code, string $message): Response
    {
        return self::json($code, ['error' => ['code' => $code, 'message' => $message]]);
    }
}
