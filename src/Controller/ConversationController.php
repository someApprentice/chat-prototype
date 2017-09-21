<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\MessageGateway;
use App\Model\Validations\Validator;
use App\Model\Entity\Conference;
use App\Model\Entity\Participant;
use App\Model\Entity\Contact;
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
                                $contact = $this->database->getUserContact($logged->getId(), $to);

                                if ($contact) {
                                    $conference = $contact->getConference();

                                    $participants = $this->database->getParticipants($conference);
                                } else {
                                    $conference = new Conference();

                                    $participants = array();

                                    $participants[] = new Participant();
                                    $participants[0]->setUser($logged->getId());

                                    $participants[] = new Participant();
                                    $participants[1]->setUser($to);

                                    $conference = $this->database->addConference($conference)->getId();

                                    foreach ($participants as $key => $participant) {
                                        $participant->setConference($conference);

                                        $participants[$key] = $this->database->addParticipant($participant);
                                    }

                                    $this->database->addUserContact($logged->getId(), $to, $conference);
                                }

                                $message = new Message();
                                $message->setAuthor($logged->getId());
                                $message->setReceiver($to);
                                $message->setContent($post['message']);

                                $this->database->addMessage($message, $participants);

                                $contact = $this->database->getUserContact($to, $logged->getId());

                                if ($contact) {
                                    if ($contact->getConference() != $conference) {
                                        throw new \Exception("Some how conferences of contacts do not match");
                                    }
                                } else {
                                    $this->database->addUserContact($to, $logged->getId(), $conference);
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

        $m = array();

        $messages = array();

        $totalCount = 0;

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;

                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if ($contact) {
                        $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                        $messages = $this->database->getMessages($logged->getId(), $contact->getConference(), $offset);   
                    }

                    $m['with'] = $with;
                    $m['offset'] =  $offset;
                    $m['count'] = count($messages);
                    $m['totalCount'] = $totalCount;
                    $m['messages'] = $messages;

                    return $m;
                } else {
                    throw new \Exception("No such contact");
                }
            } else {
                return $m;
            }
        } else {
            throw new \Exception("You are not logged."); 
        }
    }
}