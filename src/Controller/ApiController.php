<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\MessageGateway;
use App\Model\Helper;
use App\Model\Validations\Validator;
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
                    'id' => $contact->getId(),
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

                    $m = array();

                    foreach ($messages['messages'] as $message) {
                        $m[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $messages['messages'] = $m;

                    echo json_encode($messages, \JSON_FORCE_OBJECT);
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

    public function getLastMessages() {
        $logged = $this->authController->getLogged();

        $messages = array();

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                $count = $this->database->getMessagesCount($logged->getId(), $with);

                if ($this->database->getUserByColumn('id', $with)) {
                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;
                    
                    $since = Helper::getCurrentTimeWithMicroseconds();

                    $m = $this->database->getLastMessages($logged->getId(), $with, $offset);

                    $messages['with'] = $with;
                    $messages['since'] =  $since;
                    $messages['offset'] =  $offset;
                    $messages['count'] = count($m);
                    $messages['totalCount'] = $count;
                    $messages['messages'] = $m;

                    $m = array();

                    foreach ($messages['messages'] as $message) {
                        $m[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $messages['messages'] = $m;

                    echo json_encode($messages, \JSON_FORCE_OBJECT);
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

        $messages = array();

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                $count = $this->database->getMessagesCount($logged->getId(), $with);

                if ($this->database->getUserByColumn('id', $with)) {
                    $since = (isset($_GET['since']) and is_string($_GET['since'])) ? $_GET['since'] : Helper::getCurrentTimeWithMicroseconds();
                    
                    $m = $this->database->getNewMessages($logged->getId(), $with, $since);

                    $since = Helper::getCurrentTimeWithMicroseconds();

                    $messages['with'] = $with;
                    $messages['since'] =  $since;
                    $messages['count'] = count($m);
                    $messages['totalCount'] = $count;
                    $messages['messages'] = $m;

                    $m = array();

                    foreach ($messages['messages'] as $message) {
                        $m[] = array(
                            'id' => $message->getId(),
                            'author' => $message->getAuthor(),
                            'receiver' => $message->getReceiver(),
                            'name' => $message->getName(),
                            'date' => $message->getDate(),
                            'content' => $message->getContent()
                        );
                    }

                    $messages['messages'] = $m;

                    echo json_encode($messages, \JSON_FORCE_OBJECT);
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

        if ($logged) {
            if (isset($_GET['q']) and is_scalar($_GET['q'])) {
                $query = $_GET['q'];

                $results = $this->database->searchUsers($query);

                $users = array();

                foreach ($results as $key => $result) {
                    $users[] = array(
                        'id' => $result->getId(),
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