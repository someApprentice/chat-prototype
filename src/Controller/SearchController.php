<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\UserGateway;
use App\View\View;

class SearchController extends Controller
{
    protected $authController;

    protected $database;

    protected $view;

    public function __construct(AuthController $authController, UserGateway $database, View $view) {
        $this->authController = $authController;
        $this->database = $database;
        $this->view = $view;
    }

    public function search()
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            if (isset($_GET['q']) and is_scalar($_GET['q'])) {
                $query = $_GET['q'];

                $contacts = $this->database->searchContacts($query);

                $this->view->renderConversationPage(compact('logged', 'query', 'contacts'));
            } else {
                throw new \Exception("No search query");
            }
        } else {
            throw new \Exception("You are not logged."); 
        }
    }
}