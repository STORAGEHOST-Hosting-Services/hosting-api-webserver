<?php

namespace Users;

use PDO;
use PDOException;

require "../routes/users/login/JWT/JWTHandler.php";

class usersLoginModel
{
    private array $data;
    private PDO $pdo;

    public function __construct(array $data, PDO $pdo)
    {
        $this->data = $data;
        $this->pdo = $pdo;
    }

    public function authenticateUser(): array
    {
        try {
            $req = $this->pdo->prepare('SELECT storagehost_hosting.user.id, storagehost_hosting.user.last_name, storagehost_hosting.user.first_name, storagehost_hosting.user.email, storagehost_hosting.user.password, storagehost_hosting.user.activation FROM storagehost_hosting.user WHERE email = :email');
            $req->execute(array(
                ':email' => $this->data['email']
            ));
            $result = $req->fetch();

            if (is_array($result)) {
                $isPassCorrect = password_verify($this->data['password'], $result['password']);
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'username_or_password_incorrect',
                    'date' => time()
                );
            }

            // Assign the value of the activation status
            $activation_status = $result['activation'];

            if (empty($result)) {
                return array(
                    'status' => 'error',
                    'message' => 'username_or_password_incorrect',
                    'date' => time()
                );
            } else {
                if ($activation_status == 1) {
                    if ($isPassCorrect) {
                        $jwt = new JWTHandler();
                        $token = $jwt->_jwt_encode_data('http://localhost/api/user/login', array(
                            "user_id" => $result['id']
                        ));

                        return array(
                            'status' => 'success',
                            'token' => $token,
                            'date' => time()
                        );
                    } else {
                        return array(
                            'status' => 'error',
                            'message' => 'username_or_password_incorrect',
                            'date' => time()
                        );
                    }
                } else {
                    return array(
                        'status' => 'error',
                        'message' => 'account_not_enabled',
                        'date' => time()
                    );
                }
            }
        } catch (PDOException $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'date' => time()
            );
        }
    }
}