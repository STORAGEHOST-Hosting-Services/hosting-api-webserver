<?php
/** This file contains the required methods to receive and validate the form data for registering a user.
 * @author Cyril Buchs
 * @version 1.6
 */

namespace Users;

include __DIR__."/model/usersRegisterModel.php";

use PDO;

class Register
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
        if (!empty($this->form_data) && !empty($this->form_data['lastname']) && !empty($this->form_data['firstname']) && !empty($this->form_data['email']) && !empty($this->form_data['address']) && !empty($this->form_data['city']) && !empty($this->form_data['zip']) && !empty($this->form_data['country']) && !empty($this->form_data['phone']) && !empty($this->form_data['password']) && !empty($this->form_data['password_conf'])) {
            $this->form_data = [$this->form_data['lastname'], $this->form_data['firstname'], $this->form_data['email'], $this->form_data['address'], $this->form_data['zip'], $this->form_data['city'], $this->form_data['country'], $this->form_data['phone'], $this->form_data['password'], $this->form_data['password_conf']];
            //var_dump($this->form_data);

            $result = $this->checkPassword();
            if (is_null($result)) {
                // No error occurred during password treatment, proceeding
                $result = $this->trim();
                //var_dump($result);
                if (is_array($result)) {
                    // No error occurred during trim, proceeding
                    $result_validation = $this->validateFormData($result);
                    if (is_array($result_validation)) {
                        // No error occurred during validation, proceeding by calling the model
                        $model = new usersRegisterModel($this->pdo, $this->valid_form_data);
                        if ($model->checkEmailExistence()) {
                            // User does not exist in the database, proceeding
                            $result_user_creation = $model->createUser();
                            if (is_array($result_user_creation)) {
                                return $result_user_creation;
                            } else {
                                return "user_creation_error";
                            }
                        } else {
                            return "user_already_exists";
                        }

                    } else {
                        return $result_validation;
                    }
                } else {
                    return $result;
                }
            } else {
                return $result;
            }
        } else {
            return "bad_post";
        }
    }

    private function checkPassword()
    {
        // Get the password and the password confirmation from the array and assign it to local var
        $password = $this->form_data[8];
        $password_conf = $this->form_data[9];

        // Compare the two strings
        if ($password == $password_conf) {
            $final_password = $password;

            $uppercase = preg_match('@[A-Z]@', $final_password);
            $lowercase = preg_match('@[a-z]@', $final_password);
            $number = preg_match('@[0-9]@', $final_password);
            //$specialChars = preg_match('@[^\w]@', $password);

            if ($uppercase && $lowercase && $number && strlen($final_password) >= 8) {
                // Password is valid
                //var_dump($this->form_data);

                // Add hashed password in the array
                $this->valid_form_data['password'] = password_hash($final_password, PASSWORD_DEFAULT);

                // Remove password from the default array
                array_splice($this->form_data, 8);
            } else {
                return "password_not_meeting_requirements";
            }

        } else {
            // If password isn't the same as the confirmation, delete the array and print error
            return "bad_password";
        }
        return null;
    }

    private function trim()
    {
        // Trim all spaces
        if (!empty($this->form_data)) {
            $trimedFormData = array(
                'lastname' => trim($this->form_data[0]),
                'firstname' => trim($this->form_data[1]),
                'email' => trim($this->form_data[2]),
                'address' => trim($this->form_data[3]),
                'zip' => trim($this->form_data[4]),
                'city' => trim($this->form_data[5]),
                'country' => trim($this->form_data[6]),
                'phone' => trim($this->form_data[7])
            );
        } else {
            return "bad_trim";
        }

        // Give an array of unwanted chars
        $unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');

        // Create a new array who will store the validated values
        $caseFormData = array();

        //var_dump($trimedFormData);

        // Lower firstname and lastname, and put first word in upper case
        $lastname = strtolower($trimedFormData['lastname']);
        $lastname = ucwords($lastname);

        // Clear the accentuation
        $lastname = strtr($lastname, $unwanted_array);
        $caseFormData['lastname'] = $lastname;

        $firstname = strtolower($trimedFormData['firstname']);
        $firstname = ucwords($firstname);

        // Clear the accentuation
        $firstname = strtr($firstname, $unwanted_array);
        $caseFormData['firstname'] = $firstname;

        // Lower email address (email cannot have any upper case letter)
        $email = $trimedFormData['email'];
        $email = strtolower($email);
        $caseFormData['email'] = $email;

        // Lower complete address (without ZIP)
        // Also clear comma(s) in address and city
        $address = $trimedFormData['address'];
        $address = str_replace(',', '', $address);
        $address = strtolower($address);
        $address = ucwords($address);
        $caseFormData['address'] = $address;

        // Add ZIP code in the array
        $caseFormData['zip'] = $trimedFormData['zip'];

        // Add city in the array
        $city = $trimedFormData['city'];
        $city = str_replace(',', '', $city);
        $city = strtolower($city);
        $city = ucwords($city);
        $caseFormData['city'] = $city;

        // Add country in the array
        $caseFormData['country'] = ucwords($trimedFormData['country']);

        // Add the phone number in the array
        $caseFormData['phone'] = $trimedFormData['phone'];

        //var_dump($caseFormData);
        return $caseFormData;
    }

    private function validateFormData(array $caseFormData)
    {
        // Check if vars are empty
        if (empty($caseFormData) || empty($caseFormData['lastname']) || empty($caseFormData['firstname']) || empty($caseFormData['email']) || empty($caseFormData['address']) ||
            empty($caseFormData['zip']) || empty($caseFormData['city']) || empty($caseFormData['country']) || empty($caseFormData['phone'])) {
            return "error";
        }

        if (filter_var($caseFormData['lastname'], FILTER_SANITIZE_STRING)) {
            $this->valid_form_data['lastname'] = preg_replace('/\d+/u', '', $caseFormData['lastname']);
        } else {
            return "bad_last_name";
        }

        if (filter_var($caseFormData['firstname'], FILTER_SANITIZE_STRING)) {
            $this->valid_form_data['firstname'] = preg_replace('/\d+/u', '', $caseFormData['firstname']);
        } else {
            return "bad_first_name";
        }

        // Validate email
        if (filter_var($caseFormData['email'], FILTER_VALIDATE_EMAIL, FILTER_SANITIZE_EMAIL)) {
            $this->valid_form_data['email'] = $caseFormData['email'];
        } else {
            return "bad_email";
        }

        // Validate address
        if (preg_match('/[A-Za-z0-9\-,.]+/', $caseFormData['address'])) {
            $this->valid_form_data['address'] = $caseFormData['address'];
        } else {
            return "bad_address";
        }

        // Validate zip code
        if (filter_var((int)$caseFormData['zip'], FILTER_VALIDATE_INT, array("options" => array("min_range" => 1000, "max_range" => 99999)))) {
            $this->valid_form_data['zip'] = $caseFormData['zip'];
        } else {
            return "bad_zip";
        }

        // Validate city
        $validCity = $caseFormData['city'];
        if (filter_var($validCity, FILTER_SANITIZE_STRING)) {
            $validCity = preg_replace('/\d+/u', '', $validCity);
            $this->valid_form_data['city'] = $validCity;
        } else {
            return "bad_city";
        }

        // Validate country
        if ($caseFormData['country'] == "Suisse" || $caseFormData['country'] == "France") {
            $this->valid_form_data['country'] = $caseFormData['country'];
        } else {
            return "bad_country";
        }

        // Validate phone number
        $valid_phone_number = $caseFormData['phone'];
        if (preg_match("/(\b(0041|0)|\B\+41)(\s?\(0\))?(\s)?[1-9]{2}(\s)?[0-9]{3}(\s)?[0-9]{2}(\s)?[0-9]{2}\b/", $valid_phone_number) || preg_match("/^(?:(?:\+|00)33|0)\s*[1-9](?:[\s.-]*\d{2}){4}$/", $valid_phone_number)) {
            $this->valid_form_data['phone'] = $valid_phone_number;
        } else {
            return "bad_phone";
        }

        //var_dump($this->valid_form_data);

        return $this->valid_form_data;
    }
}