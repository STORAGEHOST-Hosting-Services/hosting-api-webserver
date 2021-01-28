<?php
/** This file contains the required methods to receive and validate the form data for creating an order.
 * An order will go through the following steps:
 *
 * @author Cyril Buchs
 * @version 1.6
 */

namespace Orders;

include __DIR__."/model/orderModel.php";

use PDO;

class Order
{
    private PDO $pdo;
    private array $data;
    private array $user_data;
    private array $valid_data;

    /**
     * Register constructor.
     * @param array $data
     * @param array $user_data
     * @param PDO $pdo
     */
    public function __construct(array $data, array $user_data, PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->data = $data;
        $this->user_data = $user_data;
        $this->valid_data = array();
    }

    /**
     * Method used to validate the order data
     * @return array|string
     */
    public function validateData()
    {
        //var_dump($this->user_data);

        // Validate order type
        if ($this->data['order_type'] == "app" || $this->data['order_type'] == "s1s" || $this->data['order_type'] == "s1m" || $this->data['order_type'] == "s1l" || $this->data['order_type'] == "s2s" || $this->data['order_type'] == "s2m" || $this->data['order_type'] == "s2l") {
            $this->valid_data['order_type'] = $this->data['order_type'];
        } else {
            return "bad_order_type";
        }

        // Check if user exists
        if ((new orderModel($this->pdo, $this->data, $this->user_data))->checkUserExistence()) {
            // User found
            $this->valid_data['user_id'] = $this->user_data['data']['id'];
        } else {
            return "user_not_found";
        }

        // Add full-length order type
        switch ($this->data['order_type']):
            case "app":
                $this->valid_data['order_type_name'] = "Serveur d'application (container)";
                break;
            case "s1s":
                $this->valid_data['order_type_name'] = "VPS s1.small";
                break;
            case "s1m":
                $this->valid_data['order_type_name'] = "VPS s1.medium";
                break;
            case "s1l":
                $this->valid_data['order_type_name'] = "VPS s1.large";
                break;
            case "s2s":
                $this->valid_data['order_type_name'] = "VPS s2.small";
                break;
            case "s2m":
                $this->valid_data['order_type_name'] = "VPS s2.medium";
                break;
            case "s2l":
                $this->valid_data['order_type_name'] = "VPS s2.large";
                break;

        endswitch;

        // Add order in the DB
        return (new orderModel($this->pdo, $this->valid_data, $this->user_data))->createOrder();
    }

    public function getOrders(): array
    {
        return (new orderModel($this->pdo, array(), $this->user_data))->getOrders();
    }

    private
    function validatePayment()
    {

    }
}