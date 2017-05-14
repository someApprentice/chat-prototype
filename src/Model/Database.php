<?php
namespace App\Model;

use App\Model\Entity\User;
use App\Model\Entity\Message;

class Database
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function addUser(User $user) {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO users (
                id,
                login,
                name,
                hash,
                salt
            ) VALUES (
                NULL,
                :login,
                :name,
                :hash,
                :salt
            )"
        );

        $query->execute(array(
            'login' => $user->getLogin(),
            'name' => $user->getName(),
            'hash' => $user->getHash(),
            'salt' => $user->getSalt()
        ));
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
            $user->setId($result['id']);
            $user->setLogin($result['login']);
            $user->setName($result['name']);
            $user->setHash($result['hash']);
            $user->setSalt($result['salt']);

            $results[$key]  = $user;
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

    public function addMessage(Message $message)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO messages (id, author, receiver,  date, content) VALUES (NULL, :author, :receiver, CURRENT_TIMESTAMP, :content)");
        $query->execute(array(
            'author' => $message->getAuthor(),
            'receiver' => $message->getReceiver(),
            'content' => $message->getContent()
        ));
    }

    public function getMessages($author, $receiver)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM messages WHERE (author=:author and receiver=:receiver) or (author=:receiver and receiver=:author)");
        $query->bindValue(':author', $author);
        $query->bindValue(':receiver', $receiver);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $message = new Message();
            $message->setId($result['id']);
            $message->setAuthor($this->getUserByColumn('id', $result['author']));
            $message->setReceiver($this->getUserByColumn('id', $result['receiver']));
            $message->setDate($result['date']);
            $message->setContent($result['content']);

            $results[$key] = $message;
        }

        return $results;
    }
}