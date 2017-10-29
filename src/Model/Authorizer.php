<?php
namespace App\Model;

use App\Model\Database\UserGateway;
use App\Model\Crypter;
use App\Model\Helper;
use App\Model\Entity\User;

class Authorizer
{
    protected $database;

    protected $crypter;

    public function __construct(UserGateway $database, Crypter $crypter)
    {
        $this->database = $database;
        $this->crypter = $crypter;
    }

    public function register($login, $name, $password)
    {
        $salt = Helper::generateSalt();
        $hash = Helper::generateHash($password, $salt);

        $user = new User();
        $user->setLogin($login);
        $user->setName($name);
        $user->setHash($hash);
        $user->setSalt($salt);

        $user = $this->database->addUser($user);

        $this->crypter->generateKeys($login, $password);

        $privateKey = $this->crypter->getPrivateKey($login, $password);
        $publicKey = $this->crypter->getPublicKey($login, $password);

        $this->database->addPrivateKey($user->getId(), $privateKey);
        $this->database->addPublicKey($user->getId(), $publicKey);

        return $user;
    }

    public function login($login, $password)
    {
        $user = $this->database->getUserByColumn('login', $login);

        if (!$user or $user->getHash() != Helper::generateHash($password, $user->getSalt())) {
            return false;
        }

        $expires = 60 * 60 * 24 * 30 * 12 * 3;
        
        setcookie('id', $user->getId(), time() + $expires, '/', null, null);
        setcookie('hash', $user->getHash(), time() + $expires, '/', null, null);
        setcookie('token', Helper::generateToken(), time() + $expires, '/', null, null);

        return $user;
    }

    public function logout()
    { 
        setcookie('id', null, time()-1, '/');
        setcookie('hash', null, time()-1, '/');
        setcookie('token', null, time()-1, '/');
    }

    public function getLogged($id, $hash)
    {
        $user = $this->database->getUserByColumn('id', $id);

        if ($user->getHash() != $hash) {
            return false;
        }

        return $user;
    }
}