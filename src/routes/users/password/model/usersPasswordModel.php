<?php

/**
 * This file contains the required functions to insert valid form data in the database.
 * @author Cyril Buchs
 * @version 1.7
 */

namespace Users;

use PDO;
use PDOException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class usersPasswordModel
{
    private PDO $pdo;
    private string $token;
    private string $email;
    private string $password;

    public function __construct(PDO $pdo, string $token, string $email, string $password)
    {
        $this->pdo = $pdo;
        $this->token = $token;
        $this->email = $email;
        $this->password = $password;
    }

    /**
     * Method to check if mail given by the user already exists in the database. If so, it will return an error message.
     * @return bool|null
     */
    public function checkEmailExistence(): ?bool
    {
        try {
            $req = $this->pdo->prepare('SELECT email FROM storagehost_hosting.user WHERE email = :email');
            $req->bindParam(':email', $this->email);
            $req->execute();

            // Check if request rows are higher than 0. If yes, it means that the email exists
            if ($req->rowCount() > 0) {
                return true;
            } else {
                // Email does not exist, so account creating is OK
                return false;
            }
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
        return null;
    }

    /**
     * Method used to add a password reset token in the DB
     * @return array|bool
     */
    public function addToken()
    {
        $activation_key = md5(microtime(TRUE) * 100000);
        try {
            $req = $this->pdo->prepare('UPDATE storagehost_hosting.user SET password_reset_token = :password_reset_token WHERE storagehost_hosting.user.email = :email');
            $req->execute(
                array(
                    ':password_reset_token' => $activation_key,
                    ':email' => $this->email
                )
            );
            if ($req) {
                $this->sendMail($activation_key);
                $payload = [];
                array_push($payload, array(
                    "status" => "success",
                    "data" => array(
                        'email' => $this->email,
                        'token' => $activation_key
                    )
                ));

                return $payload;
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'token_update_failed',
                    'date' => time()
                );
            }
        } catch (PDOException $e) {
            echo json_encode($e->getMessage());
        }
        return null;
    }

    public function verifyToken()
    {
        try {
            $req = $this->pdo->prepare('SELECT storagehost_hosting.user.password_reset_token FROM storagehost_hosting.user WHERE storagehost_hosting.user.email = :email');
            $req->execute(
                array(
                    ':email' => $this->email
                )
            );

            if ($req) {
                if ($req->fetch()['password_reset_token'] == $this->token) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } catch (PDOException $e) {
            echo json_encode($e->getMessage());
        }
        return null;
    }

    public function updatePassword()
    {
        try {
            $req = $this->pdo->prepare('UPDATE storagehost_hosting.user SET storagehost_hosting.user.password = :password WHERE storagehost_hosting.user.email = :email');
            $req->execute(
                array(
                    ':password' => password_hash($this->password, PASSWORD_DEFAULT),
                    ':email' => $this->email
                )
            );
            if ($req) {
                return true;
            }
        } catch (PDOException $e) {
            return array(
                'status' => 'error',
                'message' => $e->getMessage(),
                'date' => time()
            );
        }
    }

    /**
     * Method that will send an email to user when the insert pass is successful.
     * @param string $token
     */
    private function sendMail(string $token)
    {
        // Define some local vars
        $email = $this->email;

        // Encode the email and the key as URL
        $encoded_email = urlencode($email);
        $encoded_key = urlencode($token);

        // Set subject and body
        $subject = "STORAGEHOST - réinitialisation du mot de passe";
        $message = "Bonjour,<br/>
        Nous avons reçu une demande de réinitialisation de mot de passe sur le site Web de STORAGEHOST - Hosting Services.<br/><br/>
        <b>Si vous n'êtes pas à l'origine de cette demande, merci de ne pas tenir compte de cet email.</b><br/><br/>
	    Pour modifier votre mot de passe, merci de bien vouloir cliquer sur ce lien ou de le copier/coller dans un navigateur afin de l'activer :
	    <br/><br/>
        http://localhost/password_reset.php?email=" . $encoded_email . "&token=" . $encoded_key . "
        <br/>
        <br/>       
        ---------------<br/>
        Cet e-mail est généré automatiquement, merci de ne pas y répondre.<br/>
        En cas de problème, merci de contacter l'administrateur en créant un ticket sur https://helpdesk.storagehost.ch.";

        // Create new PHPMailer
        $mail = new PHPMailer(true);

        try {
            include __DIR__ . "/../../../../config/includes/mail_settings.php";

            // Define sender and recipients settings
            $mail->setFrom("notifications.storagehost@gmail.com", 'STORAGEHOST - Hosting Services');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer error: {$mail->ErrorInfo}";
        }
    }

}