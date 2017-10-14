<?php
namespace App\Model\Database;

use App\Model\Database\TableDataGateway;
use App\Model\Entity\User;
use App\Model\Entity\Contact;

class UserGateway extends TableDataGateway
{
    public function addUser(User $user)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO users (id, login, name, hash, salt) VALUES (NULL, :login, :name, :hash, :salt)");
        $query->execute(array(
            'login' => $user->getLogin(),
            'name' => $user->getName(),
            'hash' => $user->getHash(),
            'salt' => $user->getSalt()
        ));

        $id = $pdo->lastInsertId();

        $user->setId($id);

        return $user;
    }

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

    public function addUserContact($user, $contact, $conference)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO contacts (id, user, contact, conference) VALUES (NULL, :user, :contact, :conference)");
        $query->bindValue(':user', $user);
        $query->bindValue(':contact', $contact);
        $query->bindValue(':conference', $conference);
        $query->execute();
    }

    // function removeContact() - contacts.removed = 1

    public function getUserContact($user, $contact)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT contacts.id, contacts.user, contacts.contact, contacts.conference, users.name FROM contacts INNER JOIN users ON contacts.contact = users.id WHERE user=:user and contact=:contact");
        $query->bindValue(':user', $user);
        $query->bindValue(':contact', $contact);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        $contact = new Contact();
        $contact->setId($result['id']);
        $contact->setUser($result['user']);
        $contact->setContact($result['contact']);
        $contact->setConference($result['conference']);
        $contact->setName($result['name']);
        
        return $contact;
    }

    public function getUserContacts($id)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT contacts.id, contacts.user, contacts.contact, contacts.conference, users.name FROM contacts INNER JOIN users ON contacts.contact = users.id WHERE user=:id");
        $query->bindValue(':id', $id);
        $query->execute();

        $contacts = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($contacts as $key => $result) {
            $contact = new Contact();
            $contact->setId($result['id']);
            $contact->setUser($result['user']);
            $contact->setContact($result['contact']);
            $contact->setConference($result['conference']);
            $contact->setName($result['name']);

            $contacts[$key]  = $contact;
        }

        return $contacts;
    }

    //Only users, not conferences
    public function searchContacts($name)
    {
        $users = $this->searchUsers($name);

        $contacts = array();

        foreach ($users as $user) {
            $contact = new Contact();
            $contact->setContact($user->getId());
            $contact->setName($user->getName());

            $contacts[] = $contact;
        }

        return $contacts;
    }

    public function addPrivateKey($user, $privateKey)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO privateKeys (id, user, privateKey) VALUES (NULL, :user, :privateKey)");
        $query->bindValue(':user', $user);
        $query->bindValue(':privateKey', $privateKey);
        $query->execute();
    }

    public function getPrivateKey($user) {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM privateKeys WHERE user=:user");
        $query->bindValue(':user', $user);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function addPublicKey($user, $publicKey)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO publicKeys (id, user, publicKey) VALUES (NULL, :user, :publicKey)");
        $query->bindValue(':user', $user);
        $query->bindValue(':publicKey', $publicKey);
        $query->execute();
    }

    public function getPublicKey($user) {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM publicKeys WHERE user=:user");
        $query->bindValue(':user', $user);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        return $result;
    }
}