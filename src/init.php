<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container as Pimple;

use App\Model\Database\TableDataGateway;
use App\Model\Database\UserGateway;
use App\Model\Database\MessageGateway;
use App\View\View;
use App\Controller\AuthController;
use App\Controller\ConversationController;
use App\Controller\IndexController;
use App\Controller\SearchController;
use App\Controller\ApiController;

set_exception_handler(function($e) {
    header('HTTP/1.1 500 Internal Server Error');

    $message = "{$e->getMessage()} in {$e->getFile()}\nStack trace:\n{$e->getTraceAsString()}\n throw in {$e->getFile()} on line {$e->getLine()}";

    error_log($message);

    echo $message;
});

$container = new Pimple();

$container['PDO'] = function () {
    $config = parse_ini_file(__DIR__ . '/config.ini');

    $pdo = new \PDO(
        "mysql:host={$config['host']}; dbname={$config['name']}; charset=utf8",
        $config['user'],
        $config['password']
    );

    $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $query = $pdo->prepare("SET sql_mode = 'STRICT_ALL_TABLES'");
    $query->execute();

    return $pdo;
};

$container['TableDataGateway'] = function ($c) {
    return new TableDataGateway($c['PDO']);
};

$container['UserGateway'] = function ($c) {
    return new UserGateway($c['PDO']);
};

$container['MessageGateway'] = function ($c) {
    return new MessageGateway($c['PDO']);
};

$container['View'] = function ($c) {
    return new View();
};

$container['AuthController'] = function ($c) {
    return new AuthController($c['UserGateway'], $c['View']);
};

$container['ConversationController'] = function ($c) {
    return new ConversationController($c['AuthController'], $c['MessageGateway'], $c['View']);
};

$container['IndexController'] = function ($c) {
    return new IndexController($c['AuthController'], $c['ConversationController'], $c['View']);
};

$container['SearchController'] = function ($c) {
    return new SearchController($c['AuthController'], $c['UserGateway'], $c['View']);
};

$container['ApiController'] = function ($c) {
    return new ApiController($c['AuthController'], $c['MessageGateway']);
};
