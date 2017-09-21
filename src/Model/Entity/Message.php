<?php
namespace App\Model\Entity;

class Message
{
    protected $id;

    protected $author;

    protected $receiver;

    protected $name;

    protected $date;

    protected $content;

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getAuthor()
    {
        return $this->author;
    }

    public function setAuthor(int $author)
    {
        $this->author = $author;

        return $this;
    }

    public function getReceiver()
    {
        return $this->receiver;
    }

    public function setReceiver(int $receiver)
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    public function getDate()
    {
        return $this->date;
    }

    public function setDate($date) //string?
    {
        $this->date = $date;

        return $this;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent(string $content)
    {
        $this->content = $content;

        return $this;
    }
}