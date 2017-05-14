<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Pimple\Container as Pimple;

use App\Model\Database;
use App\View\View;
use App\Controller\AuthController;
use App\Controller\ConversationController;
use App\Controller\IndexController;
use App\Controller\SearchController;

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

$container['Database'] = function ($c) {
    return new Database($c['PDO']);
};

$container['View'] = function ($c) {
    return new View();
};

$container['AuthController'] = function ($c) {
    return new AuthController($c['Database'], $c['View']);
};

$container['ConversationController'] = function ($c) {
    return new ConversationController($c['AuthController'], $c['Database'], $c['View']);
};

$container['IndexController'] = function ($c) {
    return new IndexController($c['AuthController'], $c['ConversationController'], $c['View']);
};

$container['SearchController'] = function ($c) {
    return new SearchController($c['AuthController'], $c['Database'], $c['View']);
};