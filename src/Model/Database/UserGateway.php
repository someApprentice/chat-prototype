<?php
namespace App\Model\Database;

use App\Model\Database\TableDataGateway;
use App\Model\Entity\User;

class UserGateway extends TableDataGateway
{
    public function getUserByColumn($column, $value)
    {
        $pdo = $this->pdo;

        $allowedColumn = ["id", "login", "name"];

        $column = (is_scalar($column) and in_array($column, $allowedColumn)) ? $column : 'id';    

        $query = $pdo->prepare("SELECT * FROM users WHERE {$column}=:value");
        $query->bindValue(':value', $value);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;

        }

        $user = new User();
        
        $user->setId($result['id']);
        $user->setLogin($result['login']);
        $user->setName($result['name']);
        $user->setHash($result['hash']);
        $user->setSalt($result['salt']);

        return $user;
    }

    public function searchUsers($name)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM users WHERE name LIKE :name ORDER BY name ASC");
        $query->bindValue(':name', "%{$name}%");
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $user = new User();            
            $user->fillData($result);

            $results[$key] = $user;
        }

        return $results;

    }

    public function addUserContact($user, $contact)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO contacts (id, user, contact) VALUES (NULL, :user, :contact)");
        $query->bindValue(':user', $user);
        $query->bindValue(':contact', $contact);
        $query->execute();
    }

    public function getUserContact($user, $contact)
    {

        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM contacts WHERE user=:user and contact=:contact");
        $query->bindValue(':user', $user);
        $query->bindValue(':contact', $contact);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        $contact = $this->getUserByColumn('id', $result['contact']);

        return $contact;
    }

    public function getUserContacts($id)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM contacts WHERE user=:id");
        $query->bindValue(':id', $id);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $user = $this->getUserByColumn('id', $result['contact']);

            $results[$key]  = $user;
        }

        return $results;
    }
}