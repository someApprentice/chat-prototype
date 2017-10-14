<?php
namespace App\Model\Database;

use App\Model\Database\UserGateway;
use App\Model\Entity\Conference;
use App\Model\Entity\Participant;
use App\Model\Entity\Message;

class MessageGateway extends UserGateway
{
    public function addMessage(Message $message, $participants)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO messages (id, author, receiver,  date, content) VALUES (NULL, :author, :receiver, CURRENT_TIMESTAMP(6), :content)");
        $query->execute(array(
            'author' => $message->getAuthor(),
            'receiver' => $message->getReceiver(),
            'content' => $message->getContent()
        ));

        $id = $pdo->lastInsertId();

        $message->setId($id);

        foreach ($participants as $key => $participant) {
            $query = $pdo->prepare("INSERT INTO mbox (id, messageID, user, conference) VALUES (NULL, :id, :user, :conference)");
            $query->execute(array(
                'id' => $id,
                'user' => $participant->getUser(),
                'conference' => $participant->getConference()
            ));
        }

        return $message;
    }

    public function getMessages($user, $conference, $offset = 1)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT mbox.messageID, messages.author, messages.receiver, users.name, messages.date, .messages.content FROM mbox INNER JOIN messages ON mbox.messageID = messages.id INNER JOIN users ON messages.author = users.id WHERE (user=:user AND conference=:conference) AND (messages.date <= CURRENT_TIMESTAMP(6) AND messages.date >= CURRENT_TIMESTAMP - INTERVAL 7 * :offset DAY) ORDER BY messages.date ASC");
        $query->bindValue(':user', $user);
        $query->bindValue(':conference', $conference);
        $query->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $message = new Message();
            $message->setId($result['messageID']);
            $message->setAuthor($result['author']);
            $message->setReceiver($result['receiver']);
            $message->setName($result['name']);
            $message->setDate($result['date']);
            $message->setContent($result['content']);

            $results[$key] = $message;
        }

        return $results;
    }

    public function getLastMessages($user, $conference, $offset = 1)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT mbox.messageID, messages.author, messages.receiver, users.name, messages.date, .messages.content FROM mbox INNER JOIN messages ON mbox.messageID = messages.id INNER JOIN users ON messages.author = users.id WHERE (user=:user AND conference=:conference) AND (messages.date <= CURRENT_TIMESTAMP(6) - INTERVAL 7 * (:offset - 1) DAY AND messages.date >= CURRENT_TIMESTAMP(6) - INTERVAL 7 * :offset DAY) ORDER BY messages.date ASC");
        $query->bindValue(':user', $user);
        $query->bindValue(':conference', $conference);
        $query->bindValue(':offset', (int) $offset, \PDO::PARAM_INT);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $message = new Message();
            $message->setId($result['messageID']);
            $message->setAuthor($result['author']);
            $message->setReceiver($result['receiver']);
            $message->setName($result['name']);
            $message->setDate($result['date']);
            $message->setContent($result['content']);

            $results[$key] = $message;
        }

        return $results;
    }

    public function getNewMessages($user, $conference, $since)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT mbox.messageID, messages.author, messages.receiver, users.name, messages.date, .messages.content FROM mbox INNER JOIN messages ON mbox.messageID = messages.id INNER JOIN users ON messages.author = users.id WHERE (user=:user AND conference=:conference) AND messages.date >= :since ORDER BY messages.date ASC");
        $query->bindValue(':user', $user);
        $query->bindValue(':conference', $conference);
        $query->bindValue(':since', $since);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $message = new Message();
            $message->setId($result['messageID']);
            $message->setAuthor($result['author']);
            $message->setReceiver($result['receiver']);
            $message->setName($result['name']);
            $message->setDate($result['date']);
            $message->setContent($result['content']);

            $results[$key] = $message;
        }

        return $results;
    }

    public function getMessagesCount($user, $conference)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT COUNT(*) FROM mbox WHERE user=:user and conference=:conference");
        $query->bindValue(':user', $user);
        $query->bindValue(':conference', $conference);
        $query->execute();

        $result = $query->fetchColumn();

        return $result;
    }

    public function addConference(Conference $conference)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO conference (id, name) VALUES (NULL, :name)");
        $query->bindValue(':name', $conference->getName());
        $query->execute();

        $id = $pdo->lastInsertId();

        $conference->setId($id);

        return $conference;
    }

    public function getConference($id)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT * FROM conference WHERE id=:id");
        $query->bindValue(':id', $id);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        $conference = new Conference();
        $conference->setId($result['id']);
        $conference->setName($result['name']);

        return $conference;
    }

    public function addParticipant(Participant $participant)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("INSERT INTO participants (id, conference, user) VALUES (NULL, :conference, :user)");
        $query->bindValue(':conference', $participant->getConference());
        $query->bindValue(':user', $participant->getUser());
        $query->execute();

        $id = $pdo->lastInsertId();

        $participant->setId($id);

        return $participant;
    }

    public function getParticipant($id)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT participants.id, participants.conference, participants.user, users.login FROM participants INNER JOIN users ON participants.user = users.id WHERE id=:id");
        $query->bindValue(':id', $id);
        $query->execute();

        $result = $query->fetch(\PDO::FETCH_ASSOC);

        if (empty($result)) {
            return false;
        }

        $participant = new Participant();
        $participant->setId($result['id']);
        $participant->setConference($result['conference']);
        $participant->setUser($result['user']);
        $participant->setLogin($result['login']);

        return $participant;
    }

    public function getParticipants($conference)
    {
        $pdo = $this->pdo;

        $query = $pdo->prepare("SELECT participants.id, participants.conference, participants.user, users.login FROM participants INNER JOIN users ON participants.user = users.id WHERE conference=:conference");
        $query->bindValue(':conference', $conference);
        $query->execute();

        $results = $query->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as $key => $result) {
            $participant = new Participant();
            $participant->setId($result['id']);
            $participant->setConference($result['conference']);
            $participant->setUser($result['user']);
            $participant->setLogin($result['login']);

            $results[$key] = $participant;
        }

        return $results;
    }
}