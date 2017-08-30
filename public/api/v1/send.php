<?php
require_once __DIR__ . '/../../../src/init.php';

header("Content-type:application/json");

$logged = $container['ApiController']->send();