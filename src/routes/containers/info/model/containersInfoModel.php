<?php

namespace Containers;

class containersInfoModel
{
    public function listContainers(int $id, PDO $pdo)
    {
        $req = $pdo->prepare('SELECT * FROM hosting.containers LEFT JOIN hosting.applications ON hosting.containers.id = hosting.applications.instance_id WHERE containers.id = :id');
        $req->bindParam(':id', $id);
        $req->execute();

        return $req->fetchAll(PDO::FETCH_ASSOC);
    }
}