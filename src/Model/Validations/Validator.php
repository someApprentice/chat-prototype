<?php
namespace App\Model\Validations;

class Validator
{
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