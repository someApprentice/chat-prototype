<?php
require_once __DIR__ . '/../../src/init.php';

$apiMode = true;

$container['SearchController']->search($apiMode);