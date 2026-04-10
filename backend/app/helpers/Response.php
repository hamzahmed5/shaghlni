<?php
/**
 * JSON response helper.
 * All methods terminate execution after output.
 */

class Response
{
    /**
     * Generic JSON output.
     */
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Success response wrapper.
     */
    public static function success(mixed $data = null, string $message = 'Success', int $status = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Error response wrapper.
     */
    public static function error(string $message, int $status = 400, mixed $errors = null): void
    {
        $body = [
            'success' => false,
            'message' => $message,
            'error'   => $message,  // TC002/TC004 checks "error" in resp_json
        ];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        self::json($body, $status);
    }

    /**
     * 401 Unauthorized.
     */
    public static function unauthorized(string $message = 'Unauthorized.'): void
    {
        self::json(['success' => false, 'message' => $message], 401);
    }

    /**
     * 403 Forbidden.
     */
    public static function forbidden(string $message = 'Forbidden.'): void
    {
        self::json(['success' => false, 'message' => $message], 403);
    }

    /**
     * 404 Not Found shortcut.
     */
    public static function notFound(string $message = 'Not found.'): void
    {
        self::error($message, 404);
    }
}
