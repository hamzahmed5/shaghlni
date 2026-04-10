<?php
/**
 * HTTP request helper.
 * Parses JSON body, form data, query params, and uploaded files.
 */

class Request
{
    private static ?array $bodyCache = null;

    /**
     * Return parsed request body as associative array.
     * Supports application/json and multipart/form-data.
     */
    public static function body(): array
    {
        if (self::$bodyCache !== null) {
            return self::$bodyCache;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            self::$bodyCache = json_decode($raw, true) ?? [];
        } else {
            // form-data or urlencoded
            self::$bodyCache = $_POST;
        }

        return self::$bodyCache;
    }

    /**
     * Get a single field from the request body.
     */
    public static function input(string $key, mixed $default = null): mixed
    {
        return self::body()[$key] ?? $default;
    }

    /**
     * Get a query-string parameter.
     */
    public static function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get all query-string parameters.
     */
    public static function queryAll(): array
    {
        return $_GET;
    }

    /**
     * Get an uploaded file by field name.
     * Returns null if field not present or no file uploaded.
     */
    public static function file(string $key): ?array
    {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $_FILES[$key];
    }

    /**
     * Current HTTP method.
     */
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Current request URI path (without query string).
     */
    public static function path(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        $path = rtrim($path, '/') ?: '/';

        // Strip the sub-directory prefix so routes always start with /api/…
        // e.g. /jobpilot/backend/api/jobs → /api/jobs
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptDir !== '' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir)) ?: '/';
        }

        return $path;
    }

    /**
     * Return Authorization Bearer token if present.
     */
    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            return $m[1];
        }
        return null;
    }
}
