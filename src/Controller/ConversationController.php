<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\MessageGateway;
use App\Model\Validations\Validator;
use App\Model\Entity\Message;
use App\View\View;

class ConversationController extends Controller
{
    protected $authController;

    protected $database;

    protected $view;

    public function __construct(AuthController $authController, MessageGateway $database, View $view)
    {
        $this->authController = $authController;
        $this->database = $database;
        $this->view = $view;
    }

    public function run()
    {
        $logged = $this->authController->getLogged();

        $contacts = $this->getContacts();

        $messages = $this->getMessages();

        $this->view->renderConversationPage(compact('logged', 'contacts', 'messages'));
    }

    public function send()
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

                                if (!$this->database->getUserContact($to, $logged->getId())) {
                                    $this->database->addUserContact($to, $logged->getId());
                                }

                                $this->redirect("/conversation.php?with={$to}");

                                die();
                            }
                        } else {
                            $this->redirect();

                            die();
                        }
                    }
                } else {
                    throw new \Exception("No such user id");
                }
            }
        } else {
            throw new \Exception("You are not logged."); 
        }
    }

    public function getContacts()
    {
        $logged = $this->authController->getLogged();

        $contacts = array();

        if ($logged) {
            $contacts = $this->database->getUserContacts($logged->getId());

            return $contacts;
        } else {
            throw new \Exception("You are not logged."); 
        }
    }

    public function getMessages() {
        $logged = $this->authController->getLogged();

        $messages = array();

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                $count = $this->database->getMessagesCount($logged->getId(), $with);

                if ($this->database->getUserByColumn('id', $with)) {
                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;

                    $m = $this->database->getMessages($logged->getId(), $with, $offset);

                    $messages['with'] = $with;
                    $messages['offset'] =  $offset;
                    $messages['count'] = count($m);
                    $messages['totalCount'] = $count;
                    $messages['messages'] = $m;

                    return $messages;
                } else {
                    throw new \Exception("No such user id");
                }
            } else {
                return $messages;
            }
        } else {
            throw new \Exception("You are not logged."); 
        }
    }
}