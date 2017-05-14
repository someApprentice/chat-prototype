<?php
require_once __DIR__ . '/../../src/init.php';

$apiMode = true;

$logged = $container['ConversationController']->send($apiMode);