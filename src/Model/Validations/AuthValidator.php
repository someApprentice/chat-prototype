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
            $errors['login'] = "Login must be shorter than 20 English characters";
        }

        if (!AuthValidator::validateName($post['name'])) {
            $errors['name'] = "Name must be shorter than 20 English characters";
        }

        if (!AuthValidator::validatePassword($post['password'])) {
            $errors['password'] = "Password must be longer than 6 characters";
        }

        if (!AuthValidator::isPasswordsEquals($post['password'], $post['retryPassword'])) {
            $errors['retryPassword'] = "Passwords do not match";
        }

        return $errors;
    }

    public static function validateLoginPost($post)
    {
        $errors = array();
        if (!AuthValidator::validateLogin($post['login'])) {
            $errors['login'] = "Login must be shorter than 20 English characters";
        }

        if (!AuthValidator::validatePassword($post['password'])) {
            $errors['password'] = "Password must be longer than 6 characters";
        }

        return $errors;
    }
}