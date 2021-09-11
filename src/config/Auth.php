<?php

namespace Users;

use PDO;
use PDOException;

class Auth extends JWTHandler
{
    protected PDO $pdo;
    protected array $headers;
    protected $token;

    public function __construct(PDO $pdo, $headers)
    {
        parent::__construct();
        $this->pdo = $pdo;
        $this->headers = $headers;
    }

    public function isAuth(): ?array
    {
        if (array_key_exists('Authorization', $this->headers) && !empty(trim($this->headers['Authorization']))) {
            $this->token = explode(" ", trim($this->headers['Authorization']));
            if (isset($this->token[1]) && !empty(trim($this->token[1]))) {
                $token = $this->_jwt_decode_data($this->token[1]);

                if (isset($token['auth']) && isset($token['data']->user_id) && $token['auth']) {
                    return $this->fetchUser($token['data']->user_id);
                } else {
                    return null;
                }
            } else {
                return null;
            }

        } else {
            return null;
        }
    }

    protected function fetchUser($user_id): ?array
    {
        try {
            $fetch_user_by_id = "SELECT id, last_name, first_name, email FROM storagehost_hosting.user WHERE id = :id";
            $query_stmt = $this->pdo->prepare($fetch_user_by_id);
            $query_stmt->bindValue(':id', $user_id, PDO::PARAM_INT);
            $query_stmt->execute();

            if ($query_stmt->rowCount()) {
                $row = $query_stmt->fetch(PDO::FETCH_ASSOC);
                return [
                    'status' => 'success',
                    'auth' => true,
                    'data' => $row,
                    'date' => time()
                ];
            } else {
                return null;
            }
        } catch (PDOException $e) {
            return null;
        }
    }
}