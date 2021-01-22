<?php

/** This file contains the required methods to receive and validate the form data for registering a user.
 * @author Cyril Buchs
 * @version 1.6
 */

namespace Users;

include __DIR__."/model/usersLoginModel.php";

use PDO;

class Login
{
    private PDO $pdo;
    private array $form_data;
    private array $valid_form_data;

    /**
     * Register constructor.
     * @param array $form_data
     * @param PDO $pdo
     */
    public function __construct(array $form_data, PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->form_data = $form_data;
        $this->valid_form_data = array();
    }

    /**
     * Method used to receive the data and validate it.
     * @return array|string
     */
    public function getFormData()
    {

        if (!empty($this->form_data) && !empty($this->form_data['email']) && !empty($this->form_data['password'])) {
            $this->form_data = [$this->form_data['email'], $this->form_data['password']];
            //var_dump($this->form_data);

            return ($this->validateData());

        } else {
            return array(
                'status' => 'error',
                'message' => 'bad_post',
                'date' => time()
            );
        }

    }

    /**
     * Method used to validate the form data received through the Web interface.
     */
    private function validateData(): array
    {
        // Validate email
        if (filter_var($this->form_data[0], FILTER_VALIDATE_EMAIL, FILTER_SANITIZE_EMAIL)) {
            $this->valid_form_data['email'] = $this->form_data[0];

            // Add password
            $this->valid_form_data['password'] = $this->form_data[1];

            return (new usersLoginModel($this->valid_form_data, $this->pdo))->authenticateUser();
        } else {
            return array(
                'status' => 'error',
                'message' => 'bad_email',
                'date' => time()
            );
        }
    }
}