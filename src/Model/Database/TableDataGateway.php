<?php
namespace App\Model\Database;

class TableDataGateway
{
    protected $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
}