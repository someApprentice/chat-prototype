<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database;
use App\Model\Validator;
use App\Model\Entity\Message;
use App\View\View;

class ConversationController extends Controller
{
    protected $authController;

    protected $database;

    protected $view;

    public function __construct(AuthController $authController, Database $database, View $view)
    {
        $this->authController = $authController;
        $this->database = $database;
        $this->view = $view;
    }

    public function run()
    {
        $logged = $this->authController->getLogged();

        $contacts = array();

        $messages = array();

        if ($logged) {
            $contacts = $this->database->getUserContacts($logged->getId());

            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $messages = $this->database->getMessages($logged->getId(), $with);
                } else {
                    $this->redirect();

                    die();
                }
            }

            $this->view->renderConversationPage(compact('logged', 'contacts', 'messages', 'with'));
        } else {
            $this->redirect();

            die();
        }
    }

    public function send($apiMode = false)
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            if (isset($_GET['to']) and is_numeric($_GET['to'])) {
                $to = $_GET['to'];

                if ($this->database->getUserByColumn('id', $to)) {
                    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                        if (Validator::validateToken($_POST['token'])) {
                            $post['message'] = (isset($_POST['message']) and is_scalar($_POST['message'])) ? $_POST['message'] : '';

                            $post['message'] = trim($post['message']);

                            if (!empty($post['message'])) {
                                $message = new Message();
                                $message->setAuthor($logged->getId());
                                $message->setReceiver($to);
                                $message->setContent($post['message']);

                                $this->database->addMessage($message);

                                if (!$this->database->getUserContact($logged->getId(), $to)) {
                                    $this->database->addUserContact($logged->getId(), $to);
                                }

                                if (!$apiMode) {
                                    $this->redirect("/conversation.php?with={$to}");

                                    die();
                                }
                            }
                        } else {    
                            $this->redirect();

                            die();   
                        }
                    }
                } else {
                    $this->redirect();

                    die();
                }
            }
        } else {
            $this->redirect();

            die();
        }
    }
}