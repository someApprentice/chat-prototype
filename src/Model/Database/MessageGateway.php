<?php
namespace App\Model\Database;

use App\Model\Database\UserGateway;
use App\Model\Entity\Message;

class MessageGateway extends UserGateway
{
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