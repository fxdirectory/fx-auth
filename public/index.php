<?php

declare(strict_types=1);

// suppress deprecation warnings from legacy packages on newer PHP versions
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '1');

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$basePath = $_ENV['APP_BASE_PATH'] ?? '';
if ($basePath !== '') {
    $app->setBasePath($basePath);
}

$app->addRoutingMiddleware();
$app->addErrorMiddleware(true, true, true);

(require __DIR__ . '/../routes/routes.php')($app);

$app->run();
