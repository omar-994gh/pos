<?php
class Logger
{
    private static string $logFile = __DIR__ . '/../storage/app.log';

    public static function setLogFile(?string $path): void
    {
        if ($path && is_string($path)) {
            self::$logFile = $path;
        }
    }

    public static function log(string $action, string $status = 'info', ?string $message = null, array $context = []): void
    {
        $entry = [
            'time'    => date('c'),
            'user_id' => $_SESSION['user_id'] ?? null,
            'username'=> $_SESSION['username'] ?? null,
            'action'  => $action,
            'status'  => $status,
            'message' => $message,
            'context' => $context,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            'uri'     => $_SERVER['REQUEST_URI'] ?? null,
        ];
        self::write(json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', 'error', $message, $context);
    }

    private static function write(string $text): void
    {
        try {
            $dir = dirname(self::$logFile);
            if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
            @file_put_contents(self::$logFile, $text, FILE_APPEND | LOCK_EX);
        } catch (\Throwable $e) {
            // ignore logging failures
        }
    }
}
