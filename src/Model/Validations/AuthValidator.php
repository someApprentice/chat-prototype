<?php
namespace App\Model\Validations;

use App\Model\Validations\Validator;

class AuthValidator extends Validator
{
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
        if (preg_match('/^(.){6,128}$/', $password)) {
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

        if (!AuthValidator::validateLogin($post['login'])) {
            $errors['login'] = "Логин должен быть короче 20 английских символов";
        }

        if (!AuthValidator::validateName($post['name'])) {
            $errors['name'] = "Имя должно быть короче 20 русских или английских символов";
        }

        if (!AuthValidator::validatePassword($post['password'])) {
            $error['password'] = "Пароль должен быть длиньше 6 символов и короче 20";
        }

        if (!AuthValidator::isPasswordsEquals($post['password'], $post['retryPassword'])) {
            $errors['retryPassword'] = "Пароль должен быть длиньше 6 символов и короче 128";
        }

        return $errors;
    }

    public static function validateLoginPost($post)
    {
        $errors = array();
        if (!AuthValidator::validateLogin($post['login'])) {
            $errors['login'] = "Логин должен быть короче 20 английских символов";
        }

        if (!AuthValidator::validatePassword($post['password'])) {
            $errors['password'] = "Пароль должен быть длиньше 6 символов и короче 128";
        }

        return $errors;
    }
}