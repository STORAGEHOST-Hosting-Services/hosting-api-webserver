<?php

/**
 * This file contains the required methods to check the token provided by the user with the one that is stored in the DB.
 * @author Cyril Buchs
 * @version 1.1
 */

namespace Users;

use PDO;

class usersActivationModel
{
    private PDO $pdo;
    private string $email;
    private string $token;

    public function __construct(PDO $pdo, string $email, string $token)
    {
        $this->pdo = $pdo;
        $this->email = $email;
        $this->token = $token;
    }

    /**
     * Method to check if the key provided by the user matches the one that is stored in the DB.
     * If yes, it will update the status and enable the account.
     * If no, an error message will be returned to the user.
     * @return false|string
     */
    public function activateAccount()
    {
        // Decode email and token
        $this->email = urldecode($this->email);
        $this->token = urldecode($this->token);

        $result = "";
        // Get the key corresponding to the email provided
        $stmt = $this->pdo->prepare("SELECT activation_key, activation FROM storagehost_hosting.user WHERE email = :email");
        if ($stmt->execute(
                array(
                    ':email' => $this->email,
                )
            ) && $row = $stmt->fetch()) {
            $key_db = $row['activation_key']; // Get the key from the DB
            $activation_status = $row['activation']; // Get the activation status from the DB (the value is either 0 or 1)
        }

        // Testing the value of $activation_status.
        // If the value in the column of the activation status is 1 (account already enabled), we redirect to another page.
        if ($activation_status == '1') {
            $result .= "already_enabled";
            // We start the comparisons.
        } else {
            if ($this->token == $key_db) {
                // The program should then update the activation status in the DB
                $stmt = $this->pdo->prepare('UPDATE storagehost_hosting.user SET activation = 1 WHERE email = :email');
                $stmt->bindParam(':email', $this->email);
                $stmt->execute();

                // If the keys are the same, we redirect to another page.
                $result .= 'ok';

                // And finally, if the two are different, we redirect to an error page.
            } else {
                $result .= false;
            }
        }
        return $result;
    }
}