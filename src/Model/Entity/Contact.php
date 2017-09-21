<?php
namespace App\Model\Entity;

class Contact
{
    protected $id;

    protected $user;

    protected $contact;

    protected $conference;

    protected $name;

    public function getId()
    {
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;

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

    public function getContact()
    {
        return $this->contact;
    }

    public function setContact(int $contact)
    {
        $this->contact = $contact;

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

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }
}