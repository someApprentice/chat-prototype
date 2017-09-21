<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\MessageGateway;
use App\Model\Helper;
use App\Model\Validations\Validator;
use App\Model\Entity\Conference;
use App\Model\Entity\Participant;
use App\Model\Entity\Contact;
use App\Model\Entity\Message;

class ApiController extends Controller
{
    protected $authController;

    protected $database;

    public function __construct(AuthController $authController, MessageGateway $database)
    {
        $this->authController = $authController;
        $this->database = $database;
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
                                        header('HTTP/1.1 500 Internal Server Error');

                                        $json['status'] = 'Error';
                                        $json['error'] = "Some how conferences of contacts do not match";

                                        echo json_encode($json, \JSON_FORCE_OBJECT);

                                        die();
                                    }
                                } else {
                                    $this->database->addUserContact($to, $logged->getId(), $conference);
                                }

                                $json['status'] = 'Ok';

                                echo json_encode($json, \JSON_FORCE_OBJECT);
                            }
                        } else {
                            $json['status'] = 'Error';
                            $json['error'] = 'Invalid token';
                        }
                    }
                } else {
                    header('HTTP/1.1 400 Bad Request');

                    $json['status'] = 'Error';
                    $json['error'] = 'No such user id';

                    echo json_encode($json, \JSON_FORCE_OBJECT);
                }
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');

            $json['status'] = 'Error';
            $json['error'] = "You are not logged.";

            echo json_encode($json, \JSON_FORCE_OBJECT);
        }
    }

    public function getContacts()
    {
        $logged = $this->authController->getLogged();

        $contacts = array();

        if ($logged) {
            $contacts = $this->database->getUserContacts($logged->getId());

            $c = array();

            foreach ($contacts as $contact) {
                $c[] = array(
                    'id' => $contact->getContact(),
                    'name' => $contact->getName()
                );
            }

            echo json_encode($c, \JSON_FORCE_OBJECT);
        } else {
            header('HTTP/1.1 401 Unauthorized');

            $e = array();

            $e['error'] = "You are not logged.";

            echo json_encode($e, \JSON_FORCE_OBJECT);
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

                    $messages = array();

                    foreach ($m['messages'] as $message) {
                        $messages[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $m['messages'] = $messages;

                    echo json_encode($m, \JSON_FORCE_OBJECT);
                } else {
                    header('HTTP/1.1 401 Unauthorized');

                    $e = array();

                    $e['error'] = "No such contact";

                    echo json_encode($e, \JSON_FORCE_OBJECT);
                }
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');
            
            $e = array();

            $e['error'] = "You are not logged.";

            echo json_encode($c, \JSON_FORCE_OBJECT);
        }
    }

    public function getLastMessages() {
        $logged = $this->authController->getLogged();

        $m = array();

        $messages = array();

        $totalCount = 0;

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;

                    $since = Helper::getCurrentTimeWithMicroseconds();

                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if ($contact) {
                        $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                        $messages = $this->database->getLastMessages($logged->getId(), $contact->getConference(), $offset);
                    }

                    $m['with'] = $with;
                    $m['since'] =  $since;
                    $m['offset'] =  $offset;
                    $m['count'] = count($messages);
                    $m['totalCount'] = $totalCount;
                    $m['messages'] = $messages;

                    $messages = array();

                    foreach ($m['messages'] as $message) {
                        $messages[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $m['messages'] = $messages;

                    echo json_encode($m, \JSON_FORCE_OBJECT);
                } else {
                    header('HTTP/1.1 401 Unauthorized');

                    $e = array();

                    $e['error'] = "No such user id";

                    echo json_encode($e, \JSON_FORCE_OBJECT);
                }
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');
            
            $e = array();

            $e['error'] = "You are not logged.";

            echo json_encode($c, \JSON_FORCE_OBJECT);
        }
    }

    public function getNewMessages() {
        $logged = $this->authController->getLogged();

        $m = array();

        $messages = array();

        $totalCount = 0;

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $since = (isset($_GET['since']) and is_string($_GET['since'])) ? $_GET['since'] : Helper::getCurrentTimeWithMicroseconds();

                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if ($contact) {
                        $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                        $messages = $this->database->getNewMessages($logged->getId(), $contact->getConference(), $since);
                    }

                    $since = Helper::getCurrentTimeWithMicroseconds();

                    $m['with'] = $with;
                    $m['since'] =  $since;
                    $m['count'] = count($messages);
                    $m['totalCount'] = $totalCount;
                    $m['messages'] = $messages;

                    $messages = array();

                    foreach ($m['messages'] as $message) {
                        $messages[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $m['messages'] = $messages;

                    echo json_encode($m, \JSON_FORCE_OBJECT);
                } else {
                    header('HTTP/1.1 401 Unauthorized');

                    $e = array();

                    $e['error'] = "No such user id";

                    echo json_encode($e, \JSON_FORCE_OBJECT);
                }
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');
            
            $e = array();

            $e['error'] = "You are not logged.";

            echo json_encode($c, \JSON_FORCE_OBJECT);
        }
    }

    public function search()
    {
        $logged = $this->authController->getLogged();

        $users = array();

        if ($logged) {
            if (isset($_GET['q']) and is_scalar($_GET['q'])) {
                $query = $_GET['q'];

                $results = $this->database->searchContacts($query);

                foreach ($results as $key => $result) {
                    $users[] = array(
                        'id' => $result->getContact(),
                        'name' => $result->getName()
                    );
                }

                echo json_encode($users, \JSON_FORCE_OBJECT);
            } else {
                header('HTTP/1.1 400 Bad Request');

                $json['status'] = 'Error';
                $json['error'] = 'No search query';

                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');

            $json['status'] = 'Error';
            $json['error'] = "You are not logged.";

            echo json_encode($json, \JSON_FORCE_OBJECT);
        }
    }
}