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
                if ($apiMode) {
                    header('HTTP/1.1 400 Bad Request');

                    $json['status'] = 'Error';
                    $json['error'] = 'No search query';

                    echo json_encode($json, \JSON_FORCE_OBJECT);
                } else {
                    throw new \Exception("No search query");
                }
            }
        } else {
            if ($apiMode) {
                header('HTTP/1.1 401 Unauthorized');

                $json['status'] = 'Error';
                $json['error'] = "You are not logged.";

                echo json_encode($json, \JSON_FORCE_OBJECT);
            } else {
                throw new \Exception("You are not logged."); 
            }
        }
    }
}