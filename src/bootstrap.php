<?php

declare(strict_types=1);

use App\Config;
use App\Database;
use Smarty\Smarty;

define('PROJECT_ROOT', dirname(__DIR__));

$autoload = PROJECT_ROOT . '/vendor/autoload.php';

if (!is_readable($autoload)) {
    throw new RuntimeException(
        'Composer dependencies not installed. Run: composer install'
    );
}

require $autoload;

$config = Config::load(PROJECT_ROOT);

register_shutdown_function(static function () use ($config): void {
    $error = error_get_last();

    if ($error === null) {
        return;
    }

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }

    if (headers_sent()) {
        return;
    }

    http_response_code(500);

    if ($config->isDebug()) {
        echo 'Fatal error: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line'];
        return;
    }

    echo 'Internal Server Error';
});

set_exception_handler(static function (\Throwable $e) use ($config): void {
    if (headers_sent()) {
        return;
    }

    http_response_code(500);

    if ($config->isDebug()) {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
        return;
    }

    echo 'Internal Server Error';
});

$pdo = Database::createConnection($config);

$smarty = new Smarty();
$smarty->setTemplateDir(PROJECT_ROOT . '/templates');
$smarty->setCompileDir(PROJECT_ROOT . '/var/smarty/compile');
$smarty->setCacheDir(PROJECT_ROOT . '/var/smarty/cache');
$smarty->setCaching(Smarty::CACHING_OFF);
$smarty->setEscapeHtml(true);

return [
    'config' => $config,
    'pdo' => $pdo,
    'smarty' => $smarty,
];
