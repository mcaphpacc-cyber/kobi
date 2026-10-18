<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    /** Strict flat authentication object; ordinary form/query access is unaffected. */
    public function authJson(): array
    {
        $type=strtolower(trim(explode(';',(string)($_SERVER['CONTENT_TYPE']??''))[0]));
        if ($type!=='application/json') { throw new \App\Services\ApiAuthException(415,'UNSUPPORTED_MEDIA_TYPE','Use application/json.'); }
        if ((int)($_SERVER['CONTENT_LENGTH']??0)>8192) { throw new \App\Services\ApiAuthException(413,'PAYLOAD_TOO_LARGE','Request is too large.'); }
        $input=fopen('php://input','rb');
        if ($input===false) { throw new \RuntimeException('Unable to read request'); }
        try { $raw=stream_get_contents($input,8193); } finally { fclose($input); }
        return self::decodeAuthJson($raw===false?'':$raw);
    }

    public static function decodeAuthJson(string $raw): array
    {
        if (strlen($raw)>8192) { throw new \App\Services\ApiAuthException(413,'PAYLOAD_TOO_LARGE','Request is too large.'); }
        try { $object=json_decode($raw,false,8,JSON_THROW_ON_ERROR); }
        catch (\JsonException) { throw new \App\Services\ApiAuthException(400,'BAD_REQUEST','Invalid JSON object.'); }
        if (!$object instanceof \stdClass) { throw new \App\Services\ApiAuthException(400,'BAD_REQUEST','Invalid JSON object.'); }
        // Parse only JSON string/null fields. This also detects duplicate and escaped duplicate keys.
        $offset=0;$seen=[];
        $pattern='~\G\s*(?:\{|,)\s*("(?:[^"\\\\\x00-\x1f]|\\\\.)*")\s*:\s*("(?:[^"\\\\\x00-\x1f]|\\\\.)*"|null)\s*(?=,|\})~s';
        while (preg_match($pattern,$raw,$m,0,$offset)) {
            $key=json_decode($m[1],true,8,JSON_THROW_ON_ERROR);
            if (isset($seen[$key])) { throw new \App\Services\ApiAuthException(400,'BAD_REQUEST','Invalid JSON object.'); }
            $seen[$key]=true;$offset+=strlen($m[0]);
        }
        if (count(get_object_vars($object))!==count($seen) || !preg_match('/\A\s*\}\s*\z/s',substr($raw,$offset))) {
            if (!preg_match('/\A\s*\{\s*\}\s*\z/s',$raw)) { throw new \App\Services\ApiAuthException(400,'BAD_REQUEST','Invalid JSON object.'); }
        }
        return get_object_vars($object);
    }

    public static function normalizedPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : '/';
        $base = rtrim((string) config('base_path'), '/');
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            $path = substr($path, strlen($base));
        }
        return '/' . trim($path, '/');
    }

    public static function isPublicApiUri(string $uri): bool
    {
        $path = self::normalizedPath($uri);
        return $path === '/api/v1' || str_starts_with($path, '/api/v1/');
    }

    public function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function uri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    public function allGet(): array
    {
        return $_GET;
    }

    public function allPost(): array
    {
        return $_POST;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key]
            ?? $_GET[$key]
            ?? $default;
    }
}
