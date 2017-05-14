<?php
namespace App\Model;

class Validator
{
    const LOGIN_ERROR = "Логин должен быть короче 20 английских символов";
    const LOGIN_EXIST_ERROR = "Логин уже существует";
    const NAME_ERROR = "Имя должно быть короче 20 русских или английских символов";
    const PASSWORD_ERROR = "Пароль должен быть длиньше 6 символов и короче 20";
    const RETRY_PASSWORD_ERROR = "Пароли не совпадают";
    const NO_MATCHES = "Совпадений не найдено";



    public static function validateLogin($login)
    {
        if (preg_match('/^[a-zA-Z\-_]{1,20}$/', $login)) {
            return true;
        }
        
        return false;
    }

    public static function validateName($name)
    {
        if (preg_match('/^[a-zA-Zа-яёА-ЯЁ\-\'\ ]{1,20}$/u', $name)) {
            return true;
        }

        return false;
    }

    public static function validatePassword($password)
    {
        if (preg_match('/^(.){6,20}$/', $password)) {
            return true;
        }

        return false;
    }

    public static function isPasswordsEquals($password, $retryPassword) {
        if ($password === $retryPassword) {
            return true;
        }

        return false;
    }

    public static function validateRegistrationPost($post)
    {
        $errors = array();

        if (!Validator::validateLogin($post['login'])) {
            $errors['login'] = self::LOGIN_ERROR;
        }

        if (!Validator::validateName($post['name'])) {
            $errors['name'] = self::NAME_ERROR;
        }

        if (!Validator::validatePassword($post['password'])) {
            $error['password'] = self::PASSWORD_ERROR;
        }

        if (!Validator::isPasswordsEquals($post['password'], $post['retryPassword'])) {
            $errors['retryPassword'] = self::RETRY_PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateLoginPost($post)
    {
        $errors = array();
        if (!Validator::validateLogin($post['login'])) {
            $errors['login'] = self::EMAIL_ERROR;
        }

        if (!Validator::validatePassword($post['password'])) {
            $errors['password'] = self::PASSWORD_ERROR;
        }

        return $errors;
    }

    public static function validateToken($token)
    {
        if (isset($_COOKIE['token'])) {
            if ($token != "" and $_COOKIE['token'] != "" and $token === $_COOKIE['token']) {
                return true;
            }
        }
        
        return false;
    }
}