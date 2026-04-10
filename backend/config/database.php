<?php
/**
 * Database connection — PDO singleton.
 * Call DB::get() anywhere to get the shared PDO instance.
 */

class DB
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $cfg = require __DIR__ . '/app.php';
            $db  = $cfg['db'];

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $db['host'],
                $db['port'],
                $db['name'],
                $db['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $db['user'], $db['pass'], $options);
            } catch (PDOException $e) {
                // Don't expose credentials in production
                $cfg['debug']
                    ? throw $e
                    : Response::error('Database connection failed.', 500);
            }
        }

        return self::$instance;
    }

    // Prevent instantiation / cloning
    private function __construct() {}
    private function __clone()    {}
}
