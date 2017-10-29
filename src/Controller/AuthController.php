<?php
namespace App\Controller;

use App\Controller\Controller;
use App\Model\Database\UserGateway;
use App\Model\Validations\AuthValidator as Validator;
use App\Model\Authorizer;
use App\Model\Helper;
use App\Model\Entity\User;
use App\View\View;

class AuthController extends Controller
{
    protected $database;

    protected $authorizer;

    protected $view;

    public function __construct(UserGateway $database, Authorizer $authorizer, View $view)
    {
        $this->database = $database;
        $this->authorizer = $authorizer;
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
               $errors['login'] = "Login already exist";
            }

            if (empty($errors)) {
                $this->authorizer->register($post['login'], $post['name'], $post['password']);

                $this->login();

                $this->redirect();

                die();
            }
        }

        $this->view->renderRegistrationPage(compact('post', 'errors'));
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
                $user = $this->authorizer->login($post['login'], $post['password']);

                if ($user) {
                    $this->redirect();

                    die();
                } else {
                    $errors['login'] = "No matches found";
                }
            }
        }

        $this->view->renderLoginPage(compact('post', 'errors'));
    }

    public function logout() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (Validator::validateToken($_POST['token']) and $this->getLogged()) {
                $this->authorizer->logout();
            }
        }

        $this->redirect();
    }

    public function getLogged()
    {
        if (isset($_COOKIE['id']) and isset($_COOKIE['token'])) {
            $id = $_COOKIE['id'];
            $hash = $_COOKIE['hash'];

            $user = $this->authorizer->getLogged($id, $hash);

            return $user;
        }

        return false;
    }    
}