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

    public function getMessages($author, $receiver, $offset = 1)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM messages WHERE ((author=:author AND receiver=:receiver) OR (author=:receiver AND receiver=:author)) AND (date <= CURRENT_TIMESTAMP AND date >= CURRENT_TIMESTAMP - INTERVAL 7 * :offset DAY) ORDER BY date ASC");
        $query->bindValue(':author', $author);
        $query->bindValue(':receiver', $receiver);
        $query->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
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

    public function getMessagesCount($author, $receiver)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE (author=:author and receiver=:receiver) or (author=:receiver and receiver=:author)");
        $query->bindValue(':author', $author);
        $query->bindValue(':receiver', $receiver);
        $query->execute();

        $result = $query->fetchColumn();

        return $result;
    }
}