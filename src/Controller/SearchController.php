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

    public function search($apiMode = false)
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            if (isset($_GET['q']) and is_scalar($_GET['q'])) {
                $query = $_GET['q'];

                $results = $this->database->searchUsers($query);

                if ($apiMode) {
                    $users = array();

                    foreach ($results as $key => $result) {
                        $users[] = array(
                            'id' => $result->getId(),
                            'name' => $result->getName()
                        );
                    }

                    echo json_encode($users, \JSON_FORCE_OBJECT);
                } else {
                    $contacts = $results;

                    $this->view->renderConversationPage(compact('logged', 'query', 'contacts'));
                }
            } else {
                $this->redirect();
                
                die();
            }
        } else {
            $this->redirect();

            die();
        }
    }
}