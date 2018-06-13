<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Controller\AuthController;
use App\Model\Database\MessageGateway;
use App\Model\Authorizer;
use App\Model\Helper;
use App\Model\Validations\AuthValidator as Validator;
use App\Model\Entity\User;
use App\Model\Entity\Conference;
use App\Model\Entity\Participant;
use App\Model\Entity\Contact;
use App\Model\Entity\Message;

class ApiController extends Controller
{
    protected $authController;

    protected $database;

    protected $authorizer;

    public function __construct(AuthController $authController, MessageGateway $database, Authorizer $authorizer)
    {
        $this->authController = $authController;
        $this->database = $database;
        $this->authorizer = $authorizer;
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

                                $message = $this->database->addMessage($message, $participants);

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
                                $json['message'] = [
                                    'id' => $message->getId(),
                                    'author' => $message->getAuthor(),
                                    'receiver' => $message->getReceiver(),
                                    'name' => $logged->getName(),
                                    'content' => $message->getContent()
                                ];

                                echo json_encode($json, \JSON_FORCE_OBJECT);
                            }
                        } else {
                            header('HTTP/1.1 400 Bad Request');

                            $json['status'] = 'Error';
                            $json['error'] = 'Invalid token';

                            echo json_encode($json, \JSON_FORCE_OBJECT);
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

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if (!$contact) {
                        header('HTTP/1.1 500 Internal Server Error');

                        $e['error'] = "Contact of user {$logged->getId()} with {$with} wasn't found"; 

                        echo json_encode($e, \JSON_FORCE_OBJECT);

                        die();
                    }

                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;
                    
                    $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                    $messages = $this->database->getMessages($logged->getId(), $contact->getConference(), $offset);

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

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if (!$contact) {
                        header('HTTP/1.1 500 Internal Server Error');

                        $e['error'] = "Contact of user {$logged->getId()} with {$with} wasn't found"; 

                        echo json_encode($e, \JSON_FORCE_OBJECT);

                        die();
                    }

                    $offset = (isset($_GET['offset']) and is_numeric($_GET['offset'])) ? $_GET['offset'] : 1;

                    $since = Helper::getCurrentTimeWithMicroseconds();

                    $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                    $messages = $this->database->getLastMessages($logged->getId(), $contact->getConference(), $offset);

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

        if ($logged) {
            if (isset($_GET['with']) and is_numeric($_GET['with'])) {
                $with = $_GET['with'];

                if ($this->database->getUserByColumn('id', $with)) {
                    $contact = $this->database->getUserContact($logged->getId(), $with);

                    if (!$contact) {
                        header('HTTP/1.1 500 Internal Server Error');

                        $e['error'] = "Contact of user {$logged->getId()} with {$with} wasn't found"; 

                        echo json_encode($e, \JSON_FORCE_OBJECT);

                        die();
                    }

                    $since = (isset($_GET['since']) and is_string($_GET['since'])) ? $_GET['since'] : Helper::getCurrentTimeWithMicroseconds();

                    $totalCount = $this->database->getMessagesCount($logged->getId(), $contact->getConference());

                    $messages = $this->database->getNewMessages($logged->getId(), $contact->getConference(), $since);

                    if (count($messages) > 0) {
                        $since = end($messages)->getDate();
                    }

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

    public function getPrivateKey()
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            $privateKey = $this->database->getPrivateKey($logged->getId());

            if ($privateKey) {
                echo json_encode($privateKey, \JSON_FORCE_OBJECT);
            } else {
                header('HTTP/1.1 400 Bad Request');

                $json['status'] = 'Error';
                $json['error'] = "No such key.";

                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');

            $json['status'] = 'Error';
            $json['error'] = "You are not logged.";

            echo json_encode($json, \JSON_FORCE_OBJECT);
        }
    }

    public function getPublicKeys()
    {
        $logged = $this->authController->getLogged();

        if ($logged) {
            if (isset($_GET['id']) and is_numeric($_GET['id'])) {
                $publicKeys = array();

                $publicKeys[] = $this->database->getPublicKey($logged->getId())['publicKey'];
                $publicKeys[] = $this->database->getPublicKey($_GET['id'])['publicKey'];

                foreach ($publicKeys as $key => $value) {
                    if (empty($publicKeys[$key])) {
                        $publicKeys[$key] = array(
                            'status' => 'Error',
                            'error' => 'No such key.'
                        );
                    }
                }

                echo json_encode($publicKeys, \JSON_FORCE_OBJECT);
            } else {
                header('HTTP/1.1 400 Bad Request');

                $json['status'] = 'Error';
                $json['error'] = "No id query.";

                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        } else {
            header('HTTP/1.1 401 Unauthorized');

            $json['status'] = 'Error';
            $json['error'] = "You are not logged.";

            echo json_encode($json, \JSON_FORCE_OBJECT);
        }
    }

    public function register()
    {
        if ($this->authController->getLogged()) {
            $json['status'] = 'Error';
            $json['errors'] = array('login' => 'You already loged.');
            
            echo json_encode($json, \JSON_FORCE_OBJECT);
        }


        $post = array();
        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['login'] = (isset($_POST['login']) and is_scalar($_POST['login'])) ? $_POST['login'] : '';
            $post['name'] = (isset($_POST['name']) and is_scalar($_POST['name'])) ? $_POST['name'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';
            $post['retryPassword'] = (isset($_POST['retryPassword']) and is_scalar($_POST['retryPassword'])) ? $_POST['retryPassword']: '';

            $post['login'] = trim($post['login']);
            $post['name'] = trim($post['name']);
            $post['password'] = trim($post['password']);
            $post['retryPassword'] = trim($post['retryPassword']);

            $errors = Validator::validateRegistrationPost($post);

            if ($this->database->getUserByColumn('login', $post['login'])) {
               $errors['login'] = "Login already exist";
            }

            if (empty($errors)) {
                $this->authorizer->register($post['login'], $post['name'], $post['password']);

                $this->login();
            } else {
                $json['status'] = 'Error';
                $json['errors'] = $errors;

                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        }
    }

    public function login()
    {
        if ($this->authController->getLogged()) {
            $json['status'] = 'Error';
            $json['errors'] = array('login' => 'You already loged.');
            
            echo json_encode($json, \JSON_FORCE_OBJECT);
        }

        $post = array();
        $errors = array();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $post['login'] = (isset($_POST['login']) and is_scalar($_POST['login'])) ? $_POST['login'] : '';
            $post['password'] = (isset($_POST['password']) and is_scalar($_POST['password'])) ? $_POST['password'] : '';
            
            $post['login'] = trim($post['login']);
            $post['password'] = trim($post['password']);

            $errors = Validator::validateLoginPost($post);

            if (empty($errors)) {
                $user = $this->authorizer->login($post['login'], $post['password']);

                if ($user) {
                    $json = array(
                        'status' => 'Ok',
                        'user' => array(
                            'id' => $user->getId(),
                            'name' => $user->getName(),
                            'hash' => $user->getHash(),
                            'token' => Helper::generateToken()
                        )
                    );

                    echo json_encode($json, \JSON_FORCE_OBJECT);
                } else {
                    $errors['login'] = "No matches found";

                    $json['status'] = 'Error';
                    $json['errors'] = $errors;

                    echo json_encode($json, \JSON_FORCE_OBJECT);
                }
            } else {
                $json['status'] = 'Error';
                $json['errors'] = $errors;


                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        }
    }

    public function getLogged()
    {
        if (isset($_COOKIE['id'])) {
            if (isset($_COOKIE['token'])) {
                $id = $_COOKIE['id'];
                $hash = $_COOKIE['hash'];

                $user = $this->authorizer->getLogged($id, $hash);

                if ($user) {
                    $json = array(
                        'status' => 'Ok',
                        'id' => $user->getId(),
                        'name' => $user->getName(),
                        'hash' => $user->getHash()
                    );

                    echo json_encode($json, \JSON_FORCE_OBJECT);
                } else {
                    $json['status'] = 'Error';
                    $json['error'] = "Hashes doesn't match";
                }
            } else {
                $json['status'] = 'Error';
                $json['error'] = 'Invalid token';

                echo json_encode($json, \JSON_FORCE_OBJECT);
            }
        } else {
            $json['status'] = 'Error';
            $json['error'] = 'No such id';

            echo json_encode($json, \JSON_FORCE_OBJECT);
        }
    }   
}