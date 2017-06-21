<?php
require_once __DIR__ . '/../../../src/init.php';

header("Content-type:application/json");

$apiMode = true;

$logged = $container['ConversationController']->send($apiMode);