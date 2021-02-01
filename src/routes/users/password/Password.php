<?php
/** This file contains the required methods to receive and validate the form data for registering a user.
 * @author Cyril Buchs
 * @version 1.6
 */

namespace Users;

include __DIR__ . "/model/usersPasswordModel.php";

use PDO;

class Password
{
    private PDO $pdo;
    private string $token;
    private string $email;
    private string $password;

    /**
     * Password constructor.
     * @param PDO $pdo
     * @param string $token
     * @param string $email
     * @param string $password
     */
    public function __construct(PDO $pdo, string $token, string $email, string $password)
    {
        $this->pdo = $pdo;
        $this->token = $token;
        $this->email = $email;
        $this->password = $password;
    }

    public function sendEmail()
    {
        $model = new usersPasswordModel($this->pdo, $this->token, $this->email, $this->password);
        if ($model->checkEmailExistence()) {
            return $model->addToken();
        } else {
            return false;
        }
    }

    public function updateUser()
    {
        $model = new usersPasswordModel($this->pdo, $this->token, $this->email, $this->password);
        if ($model->verifyToken()) {
            return $model->updatePassword();
        } else {
            return false;
        }
    }
}