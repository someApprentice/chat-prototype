<?php
namespace App\Model\Entity;

class Participant
{
    protected $id;

    protected $conference;

    protected $user;

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
}