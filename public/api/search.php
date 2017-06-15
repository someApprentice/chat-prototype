<?php
require_once __DIR__ . '/../../src/init.php';

$apiMode = true;

header("Content-type:application/json");

$container['SearchController']->search($apiMode);