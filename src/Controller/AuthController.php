<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Model\Database;
use App\Model\Validator;
use App\Model\Helper;
use App\Model\Entity\User;
use App\View\View;

class AuthController extends Controller
{
    protected $database;

    protected $view;

    public function __construct(Database $database, View $view)
    {
        $this->database = $database;
        $this->view = $view;
    }

    public function register()
    {
        if ($this->getLogged()) {
            $this->redirect();

            die();
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
               $errors['login'] = Validator::LOGIN_EXIST_ERROR;
            }

            if (empty($errors)) {
                $salt = Helper::generateSalt();
                $hash = Helper::generateHash($post['password'], $salt);

                $user = new User();
                $user->setLogin($post['login']);
                $user->setName($post['name']);
                $user->setHash($hash);
                $user->setSalt($salt);

                $this->database->addUser($user);

                $this->login();

                $this->redirect();

                die();
            }
        }

        $this->view->registration(compact('post', 'errors'));
    }

    public function login()
    {
        if ($this->getLogged()) {
            $this->redirect();

            die();
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
                $user = $this->database->getUserByColumn('login', $post['login']);

                if ($user) {
                    if ($user->getHash() == Helper::generateHash($post['password'], $user->getSalt())) {
                        $expires = 60 * 60 * 24 * 30 * 12 * 3;

                        var_dump($user->getId());

                        setcookie('id', $user->getId(), time() + $expires, '/', null, null);
                        setcookie('hash', $user->getHash(), time() + $expires, '/', null, null);
                        setcookie('token', Helper::generateToken(), time() + $expires, '/', null, null);

                        $this->redirect();

                        die();
                    } else {
                        $errors['login'] = Validator::NO_MATCHES;
                    }
                } else {
                    $errors['login'] = Validator::NO_MATCHES;
                }
            }
        }

        $this->view->login(compact('post', 'errors'));
    }

    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (Validator::validateToken($_POST['token']) and $this->getLogged()) {
                setcookie('id', null, time()-1, '/');
                setcookie('hash', null, time()-1, '/');
                setcookie('token', null, time()-1, '/');
            }
        }

        $this->redirect();
    }

    public function getLogged()
    {
        if (isset($_COOKIE['id'])) {
            $user = $this->database->getUserByColumn('id', $_COOKIE['id']);

            if (isset($_COOKIE['token'])) {
                if ($user->getHash() == $_COOKIE['hash']) {
                    return $user;
                }
            }
        }

        return false;
    }    
}