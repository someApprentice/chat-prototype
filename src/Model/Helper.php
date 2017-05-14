<?php
namespace App\Model;

class Helper
{
    public static function generateSalt()
    {
        $salt = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ.*-^%$#@!?%&%_=+<>[]{}'), 0, 44);

        return $salt;
    }

    public static function generateHash($password, $salt) {
        $hash = md5($password . $salt);
        
        return $hash;
    }

    public static function generateToken()
    {
        $token = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 32);
        
        return $token;
    }

    public static function getToken() 
    {
        if (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        } else {
            $token = $this->generateToken();
        }

        return $token;
    }
}