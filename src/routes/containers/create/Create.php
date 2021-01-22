<?php

namespace Containers;

require __DIR__ . "/model/containersCreateModel.php";

class Create
{
    private $container_data;
    private $pdo;

    public function __construct(array $container_data, \PDO $pdo)
    {
        $this->container_data = $container_data;
        $this->pdo = $pdo;
    }

    public function validateData()
    {
        var_dump($this->container_data);
        return "";
    }

    public function createVm()
    {
        return (new containersCreateModel())->createContainer($this->container_data, $this->pdo);
    }

}