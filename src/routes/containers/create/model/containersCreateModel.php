<?php

namespace Containers;

class containersCreateModel
{
    public function createContainer(array $container_data, \PDO $pdo)
    {
        $req = $pdo->prepare('INSERT INTO hosting.containers(name, username, password, state, application_id, user_id, networking_id) VALUES (:name, :username, :password, :state, :application, :user_id, :networking)');
        if ($req->execute(array(
            ':name' => $container_data['name'],
            ':username' => $container_data['username'],
            ':password' => password_hash($container_data['password'], PASSWORD_DEFAULT),
            ':state' => false,
            ':application' => $container_data['application'],
            ':user_id' => $container_data['user_id'],
            ':networking' => $container_data['networking_id']
        ))) {
            return true;
        } else {
            // An error occurred
            return false;
        }


    }
}