<?php
namespace App\View;

class View
{

    public function renderRegistrationPage(array $varibles = array())
    {
        $this->render('/templates/head.html');
        $this->render('/templates/registration.phtml', $varibles);
        $this->render('/templates/foot.html');
    }

    public function renderLoginPage(array $varibles = array())
    {
        $this->render('/templates/head.html');
        $this->render('/templates/login.phtml', $varibles);
        $this->render('/templates/foot.html');
    }

    public function renderConversationPage(array $varibles = array())
    {
        extract($varibles);

        $this->render('/templates/head.html');
        $this->render('/templates/header.phtml', compact('logged'));
        $this->render('/templates/chat.phtml', compact('contacts', 'messages', 'with'));
        $this->render('/templates/foot.html');
    }

    public function render($path, array $varibles = array())
    {
        extract($varibles);

        $path = __DIR__ . '/../../' . $path;

        if (file_exists($path)) {
            include $path;
        } else {
            throw new \Exception("Invalid template path");
        }
    }
}