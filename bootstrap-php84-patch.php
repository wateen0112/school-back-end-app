<?php

/**
 * Laravel 7 + PHP 8.4 bootstrap patches. Re-run after `composer install` refreshes laravel/framework.
 */
declare(strict_types=1);

$projectRoot = __DIR__;
$base = $projectRoot . '/vendor/laravel/framework/src';

$patch = static function (string $path, callable $apply): void {
    if (! is_file($path)) {
        fwrite(STDERR, "Missing file: {$path}\n");

        return;
    }
    $contents = file_get_contents($path);
    if ($contents === false) {
        fwrite(STDERR, "Unreadable: {$path}\n");

        return;
    }

    $updated = $apply($contents);

    if ($updated === null || $updated === $contents) {
        fwrite(STDERR, "No change: {$path}\n");

        return;
    }

    file_put_contents($path, $updated);
    echo "Patched: {$path}\n";
};

$patch($base . '/Illuminate/Foundation/Bootstrap/HandleExceptions.php', static function (string $c): ?string {
    if (strpos($c, 'E_USER_DEPRECATED') !== false) {
        return null;
    }

    $before = "    {\n        if (error_reporting() & \$level) {\n";
    $normalized = str_replace("\r\n", "\n", $c);
    if (strpos($normalized, $before) !== false) {
        $after = "    {\n        if (in_array(\$level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {\n            return;\n        }\n\n        if (error_reporting() & \$level) {\n";

        return str_replace($before, $after, $normalized);
    }

    $before = "    {\r\n        if (error_reporting() & \$level) {\r\n";
    if (strpos($c, $before) === false) {
        return null;
    }

    $after = "    {\r\n        if (in_array(\$level, [E_DEPRECATED, E_USER_DEPRECATED], true)) {\r\n            return;\r\n        }\r\n\r\n        if (error_reporting() & \$level) {\r\n";

    return str_replace($before, $after, $c);
});

$patch($base . '/Illuminate/Log/Logger.php', static function (string $c): ?string {
    $next = preg_replace(
        '/public function __construct\(LoggerInterface \$logger, Dispatcher \$dispatcher = null\)/',
        'public function __construct(LoggerInterface $logger, ?Dispatcher $dispatcher = null)',
        $c,
        1,
        $count
    );

    return $count ? $next : null;
});

$patch($base . '/Illuminate/Database/Migrations/Migrator.php', static function (string $c): ?string {
    $next = preg_replace(
        '/(Filesystem \$files,\s*)Dispatcher (\$dispatcher = null)/',
        '$1?Dispatcher $2',
        $c,
        1,
        $count
    );

    return $count ? $next : null;
});

$patch($base . '/Illuminate/Support/Collection.php', static function (string $c): ?string {
    $next = preg_replace(
        '/public static function times\(\$number, callable \$callback = null\)/',
        'public static function times($number, ?callable $callback = null)',
        $c,
        1,
        $count
    );

    return $count ? $next : null;
});

echo "Done.\n";
