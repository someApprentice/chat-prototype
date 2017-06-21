<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\View\View;

class IndexController extends Controller
{
    protected $authController;

    protected $conversationController;

    protected $view;

    public function __construct(AuthController $authController, ConversationController $conversationController, View $view)
    {
        $this->authController = $authController;
        $this->conversationController = $conversationController;
        $this->view = $view;
    }

    public function run()
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            $this->conversationController->run();
        } else {
            $this->view->renderLoginPage();
        }
    }
}