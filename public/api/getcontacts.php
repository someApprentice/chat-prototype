<?php
require_once __DIR__ . '/../../src/init.php';

$logged = $container['AuthController']->getLogged();

if ($logged) {
    $contactsObject = $container['Database']->getUserContacts($logged->getId());

    $contacts = array();

    foreach ($contactsObject as $contact) {
        $contacts[] = array(
            'id' => $contact->getId(),
            'name' => $contact->getName()
        );
    }

    echo json_encode($contacts, \JSON_FORCE_OBJECT);
}