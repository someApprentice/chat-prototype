<?php
namespace App\Controller;

class Controller
{
    public static function redirect($location = "/index.php")
    {
        if (!preg_match('!^/([^/]|\\Z)!', $location, $matches)) {
            $location = "/index.php";
        }

        header("Location: " . $location);
    }
}