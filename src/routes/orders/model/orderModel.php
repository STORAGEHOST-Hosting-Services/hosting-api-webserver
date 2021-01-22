<?php

/**
 * This file contains the required functions to insert valid form data in the database.
 * @author Cyril Buchs
 * @version 1.7
 */

namespace Orders;

use Config;
use DateTime;
use PDO;
use PDOException;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class orderModel
{
    private PDO $pdo;
    private array $form_data;
    private array $user_data;
    private int $order_id;

    public function __construct(PDO $pdo, array $data, array $user_data)
    {
        $this->pdo = $pdo;
        $this->form_data = $data;
        $this->user_data = $user_data;
        $this->order_id = 0;
    }

    /**
     * Method to check if the user exists.
     * @return bool|null
     */
    public function checkUserExistence(): ?bool
    {
        $user_id = $this->user_data['data']['id'];

        try {
            $req = $this->pdo->prepare('SELECT email FROM storagehost_hosting.user WHERE id = :id');
            $req->bindParam(':id', $user_id);
            $req->execute();

            // Check if request rows are higher than 0. If yes, it means that the user_id exists
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
     * Method used to insert data in the DB.
     * @return array|bool
     */
    public function createOrder()
    {
        $date = new DateTime();
        $date = $date->format('Y-m-d H:i:s');

        try {
            $req = $this->pdo->prepare('INSERT INTO storagehost_hosting.`order`(order_type, date, payment_status, user_id) VALUES (:order_type, :date, :payment_status, :user_id)');
            $req->execute(
                array(
                    ':order_type' => $this->form_data['order_type'],
                    ':date' => $date,
                    ':payment_status' => 0,
                    ':user_id' => $this->form_data['user_id']
                )
            );
            if ($req) {
                $this->order_id = $this->pdo->lastInsertId();
                $this->sendMail();
                $payload = [];
                array_push($payload, array(
                    "status" => "success",
                    "data" => array(
                        'order_id' => $this->order_id,
                        'order_type' => $this->form_data['order_type_name'],
                        'user_id' => $this->user_data['data']['id'],
                        'date' => $date
                    )
                ));

                return $payload;
            } else {
                return array(
                    'status' => 'error',
                    'message' => 'order_creation_failed',
                    'date' => time()
                );
            }
        } catch (PDOException $e) {
            echo json_encode($e->getMessage());
        }
        return null;
    }

    /**
     * Method that will send an email to user when the insert pass is successful.
     */
    private function sendMail()
    {
        // Define some local vars
        $email = $this->user_data['data']['email'];
        $date = new DateTime();
        $date = $date->format('d.m.Y H:i');

        // Set subject and body
        $subject = "STORAGEHOST - votre commande n° " . $this->order_id;
        $message = "Bonjour,<br/>
        Nous vous confirmons la réception de votre commande sur le site Web de STORAGEHOST - Hosting Services.<br/><br/>
        <b>Résumé de votre commande :</b><br/><br/>
	    <strong>Type d'offre :</strong> " . $this->form_data['order_type_name'] . "<br/>
	    <strong>Date :</strong> " . $date . "
	    <br/><br/>
        Pour gérer votre nouvelle machine, merci de vous rendre sur https://panel.storagehost.ch/
        <br/>
        <br/>       
        ---------------<br/>
        Cet e-mail est généré automatiquement, merci de ne pas y répondre.<br/>
        En cas de problème, merci de contacter l'administrateur en créant un ticket sur https://helpdesk.storagehost.ch.";

        // Create new PHPMailer
        $mail = new PHPMailer(true);

        try {
            include "../config/includes/mail_settings.php";

            // Define sender and recipients settings
            $mail->setFrom(Config::MAIL_USERNAME, 'STORAGEHOST - Hosting Services');
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