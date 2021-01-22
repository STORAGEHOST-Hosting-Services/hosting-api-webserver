<?php

namespace Users;

use PDO;
use usersDeleteModel;

require __DIR__."/model/usersDeleteModel.php";

class Delete
{
    private PDO $pdo;
    private int $id;

    /**
     * delete constructor.
     * @param PDO $pdo
     * @param int $id
     */
    public function __construct(PDO $pdo, int $id)
    {
        $this->pdo = $pdo;
        $this->id = $id;
    }

    public function deleteUser()
    {
        return (new usersDeleteModel($this->pdo, $this->id))->getUser();
    }
}