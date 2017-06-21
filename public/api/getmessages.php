<?php
require_once __DIR__ . '/../../src/init.php';

header("Content-type:application/json");

$logged = $container['AuthController']->getLogged();

if ($logged) {
    if (isset($_GET['with']) and is_numeric($_GET['with'])) {
        $with = $_GET['with'];

        if ($container['Database']->getUserByColumn('id', $with)) {
            $messages = array();

            $messagesObject = $container['Database']->getMessages($logged->getId(), $with);

            foreach ($messagesObject as $message) {
                $messages[] = array(
                    'id' => $message->getId(),
                    'author' => $message->getAuthor()->getName(),
                    'authorID' => $message->getAuthor()->getId(),
                    'receiver' => $message->getReceiver()->getName(),
                    'date' => $message->getDate(),
                    'content' => $message->getContent()
                );
            }

            echo json_encode($messages, \JSON_FORCE_OBJECT);
        }
    }
}