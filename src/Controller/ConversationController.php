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

                                if ($apiMode) {
                                    $json['status'] = 'Ok';

                                    echo json_encode($json, \JSON_FORCE_OBJECT);
                                } else {
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
                    if ($apiMode) {
                        $json['status'] = 'Error';
                        $json['error'] = 'No such id to send';

                        echo json_encode($json, \JSON_FORCE_OBJECT);
                    } else {
                        throw new \Exception("No such id to send");
                    }
                }
            }
        } else {
            if ($apiMode) {
                $json['status'] = 'Error';
                $json['error'] = "You are not logged.";

                echo json_encode($json, \JSON_FORCE_OBJECT);
            } else {
                throw new \Exception("You are not logged."); 
            }
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

                echo json_encode($e, \JSON_FORCE_OBJECT);
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

                $count = $this->database->getMessagesCount($logged->getId(), $with);

                if ($this->database->getUserByColumn('id', $with)) {
                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;

                    $m = $this->database->getMessages($logged->getId(), $with, $offset);

                    $messages['with'] = $with;
                    $messages['offset'] =  $offset;
                    $messages['count'] = count($m);
                    $messages['totalCount'] = $count;
                    $messages['messages'] = $m;

                    if ($apiMode) {
                        $m = array();

                        foreach ($messages['messages'] as $message) {
                            $m[] = array(
                                'id' => $message->getId(),
                                'author' => $message->getAuthor()->getName(),
                                'authorID' => $message->getAuthor()->getId(),
                                'receiver' => $message->getReceiver()->getName(),
                                'date' => $message->getDate(),
                                'content' => $message->getContent()
                            );
                        }

                        $messages['messages'] = $m;

                        echo json_encode($messages, \JSON_FORCE_OBJECT);
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