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

        if (isset($_GET['with']) and is_numeric($_GET['with'])) {
            $with = $_GET['with'];
        }

        $contacts = $this->getContacts();

        $messages = $this->getMessages();

        $this->view->renderConversationPage(compact('logged', 'contacts', 'messages', 'with'));
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

                                if (!$this->database->getUserContact($to, $logged->getId())) {
                                    $this->database->addUserContact($to, $logged->getId());
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

    public function getContacts($apiMode = false)
    {
        $logged = $this->authController->getLogged();

        $contacts = array();

        if ($logged) {
            $contacts = $this->database->getUserContacts($logged->getId());

            if ($apiMode) {
                $c = array();

                foreach ($contacts as $contact) {
                    $c[] = array(
                        'id' => $contact->getId(),
                        'name' => $contact->getName()
                    );
                }

                echo json_encode($c, \JSON_FORCE_OBJECT);
            } else {
                return $contacts;
            }
        } else {
            if ($apiMode) {
                $e = array();

                $e['error'] = "You are not logged.";

                echo json_encode($c, \JSON_FORCE_OBJECT);
            } else {
                throw new \Exception("You are not logged."); 
            }
        }
    }

    public function getMessages($apiMode = false) {
        $logged = $this->authController->getLogged();

        $messages = array();

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $messages = $this->database->getMessages($logged->getId(), $with);

                    if ($apiMode) {
                        $m = array();

                        foreach ($messages as $message) {
                            $m[] = array(
                                'id' => $message->getId(),
                                'author' => $message->getAuthor()->getName(),
                                'authorID' => $message->getAuthor()->getId(),
                                'receiver' => $message->getReceiver()->getName(),
                                'date' => $message->getDate(),
                                'content' => $message->getContent()
                            );
                        }

                        echo json_encode($m, \JSON_FORCE_OBJECT);
                    } else {
                        return $messages;
                    }
                } else {
                    if ($apiMode) {
                        $e = array();

                        $e['error'] = "No such user id";

                        echo json_encode($e, \JSON_FORCE_OBJECT);
                    } else {
                        throw new \Exception("No such user id");
                    }
                }
            } else {
                return $messages;
            }
        } else {
            if ($apiMode) {
                $e = array();

                $e['error'] = "You are not logged.";

                echo json_encode($c, \JSON_FORCE_OBJECT);
            } else {
                throw new \Exception("You are not logged."); 
            }
        }
    }
}