<?php
namespace App\Model\Entity;

class Participant
{
    protected $id;

    protected $conference;

    protected $user;

    protected $login; //gpg key id

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    public function getConference()
    {
        return $this->conference;
    }

    public function setConference(int $conference)
    {
        $this->conference = $conference;

        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(int $user)
    {
        $this->user = $user;

        return $this;
    }

    public function getLogin()
    {
        return $this->login;
    }

    public function setLogin(string $login)
    {
        $this->login = $login;

        return $this;
    }
}