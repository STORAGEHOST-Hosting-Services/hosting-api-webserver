<?php

require __DIR__."/../../vendor/autoload.php";
require __DIR__."/../config/SQLConnection.php";

/**
 * Users
 */
require __DIR__."/../routes/users/register/register.php";
require __DIR__."/../routes/users/login/login.php";
require __DIR__."/../routes/users/delete/delete.php";
require __DIR__."/../routes/users/info/info.php";
require __DIR__."/../routes/users/activation/usersActivationModel.php";

/**
 * Containers
 */
require __DIR__."/../routes/containers/create/create.php";
require __DIR__."/../routes/containers/info/info.php";
require __DIR__."/../routes/containers/power/power.php";
require __DIR__."/../routes/containers/delete/delete.php";

/**
 * VMs
 */
require __DIR__."/../routes/vms/create/create.php";
require __DIR__."/../routes/vms/info/info.php";
require __DIR__."/../routes/vms/power/power.php";
require __DIR__."/../routes/vms/delete/delete.php";

/**
 * Orders
 */
require __DIR__."/../routes/orders/Order.php";

/**
 * Auth
 */
require __DIR__."/../config/Auth.php";

use Orders\Order;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Container;
use Users\Auth;
use Users\Info;
use Users\Login;
use Users\Register;
use Vms\Create;
use Vms\Delete;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];

$c = new Container($configuration);

$app = new App($c);

$container = $app->getContainer();

$container['pdo'] = function () {
    return (new SQLConnection())->connect();
};

/**
 * ---------------------------------------------------------------------------------------------------------------------
 * AUTHENTICATED ACTIONS
 * ---------------------------------------------------------------------------------------------------------------------
 */

/**
 * -----------------------------------------------------------------------
 * USER SECTION
 * -----------------------------------------------------------------------
 */

// Get all users
$app->get('/api/users', function (Request $request, Response $response) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        return $response->withStatus(200)->withJson((new Info(0, $this->pdo))->listUsers());
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

$app->get('/api/user/{id}', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        if (isset($args['id'])) {
            $id = $args['id'];

            if (is_numeric($id)) {
                return $response->withStatus(200)->withJson((new Info($id, $this->pdo))->listUserInfo());

            } else {
                return $response->withStatus(400)->withJson(
                    array(
                        'status' => 'error',
                        'message' => "missing_parameter_id",
                        'date' => time()
                    ));
            }
        } else {
            $response->withStatus(400)->withJson(
                array(
                    'status' => 'error',
                    'message' => "missing_parameter_id",
                    'date' => time()
                ));
        }

        return $response;
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

/**$app->get('/api/user/{id}/containers', function (Request $request, Response $response, $args) {
 * if (isset($args['id']) && (int)$args['id']) {
 * $id = $args['id'];
 *
 * $containers = (new Info($id, $this->pdo))->listContainers();
 *
 * return $response->withStatus(200)->withJson($containers);
 * } else {
 * return $response->withStatus(400)->withJson('{"error":"Missing required parameter ID"}');
 * }
 * });*/

$app->get('/api/user/{id}/vms', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        if (isset($args['id']) && (int)$args['id']) {
            $id = $args['id'];

            $containers = (new Info($id, $this->pdo))->listVms();

            return $response->withStatus(200)->withJson($containers);
        } else {
            return $response->withStatus(400)->withJson(
                array(
                    'status' => 'error',
                    'message' => "missing_parameter_id",
                    'timestamp' => time()
                ));
        }
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

$app->delete('/api/user/delete/{id}', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        $id = $args['id'];

        $result = (new Users\Delete($this->pdo, $id))->deleteUser();

        if (strpos($result, "Integrity constraint violation")) {
            return $response->withStatus(400)->withJson(array(
                'status' => 'error',
                'message' => 'user_has_orders',
                'date' => time()
            ));
        } elseif ($result['status'] == 'success') {
            return $response->withStatus(200)->withJson($result);

        } else {
            return $response->withStatus(404)->withJson($result);
        }
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

/**
 * -----------------------------------------------------------------------
 * ORDER SECTION
 * -----------------------------------------------------------------------
 */

$app->post('/api/order/create', function (Request $request, Response $response) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        $user_data = $auth->isAuth();
        $body = $request->getParsedBody();

        if (isset($body) && !empty($body)) {
            $result = (new Order((array)$body, (array)$user_data, $this->pdo))->validateData();
            if (is_array($result)) {
                return $response->withStatus(201)->withJson($result);
            } else {
                return $response->withStatus(400)->withJson(array(
                    'status' => 'error',
                    'message' => $result,
                    'timestamp' => time()
                ));
            }
        } else {
            return $response->withStatus(400)->withJson(array(
                'status' => 'error',
                'message' => 'missing_body',
                'timestamp' => time()
            ));
        }
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

/**
 * -----------------------------------------------------------------------
 * VM SECTION
 * -----------------------------------------------------------------------
 */

$app->post('/api/vm/create', function (Request $request, Response $response) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        // Get user data
        $user_data = $auth->isAuth();
        $body = $request->getParsedBody();

        if (isset($body) && !empty($body)) {
            $result = (new Create((array)$body, (array)$user_data, $this->pdo))->validateData();

            if (array_search('error', $result)) {
                return $response->withStatus(400)->withJson(
                    array(
                        'status' => 'error',
                        'message' => $result,
                        'timestamp' => time()
                    ));
            } else {
                return $response->withStatus(201)->withJson(
                    array(
                        'status' => 'success',
                        'data' => $result,
                        'timestamp' => time()
                    ));
            }
        } else {
            return $response->withStatus(400)->withJson(
                array(
                    'status' => 'error',
                    'message' => "missing_body",
                    'timestamp' => time()
                ));
        }
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

$app->get('/api/vm/{id}/info', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        if (isset($args['id']) && (int)$args['id']) {
            $id = $args['id'];

            $vms = (new \Vms\Info($id, $this->pdo))->listVms();

            return $response->withStatus(200)->withJson($vms);
        } else {
            return $response->withStatus(400)->withJson(
                array(
                    'status' => 'error',
                    'message' => "missing_parameter_id",
                    'timestamp' => time()
                ));
        }
    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

$app->patch('/api/vm/{id}/power', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        if (is_numeric($args['id'])) {
        } else {
            return $response->withStatus(400)->withJson(array(
                'status' => 'error',
                'message' => 'missing_parameter_id',
                'date' => time()
            ));
        }

    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

$app->delete('/api/vm/{id}/delete', function (Request $request, Response $response, $args) {
    $headers = getallheaders();
    $auth = new Auth($this->pdo, $headers);

    if ($auth->isAuth()) {
        if (is_numeric($args['id'])) {
            $user_data = $auth->isAuth();
            $data = (new Delete($this->pdo, (int)$args['id'], (array)$user_data))->deleteVm();
            var_dump($data);
        } else {
            return $response->withStatus(400)->withJson(array(
                'status' => 'error',
                'message' => 'missing_parameter_id',
                'date' => time()
            ));
        }

    } else {
        return $response->withStatus(401)->withJson(array(
            'status' => 'error',
            'message' => 'unauthorized',
            'date' => time()
        ));
    }
});

/**
 * ---------------------------------------------------------------------------------------------------------------------
 * DOCKER SECTION
 * ---------------------------------------------------------------------------------------------------------------------
 */

/**
 * $app->post('/api/docker/create', function (Request $request, Response $response) {
 *
 * });
 *
 * $app->get('/api/docker/{id}/info', function (Request $request, Response $response, $args) {
 * if (isset($args['id']) && (int)$args['id']) {
 * $id = $args['id'];
 *
 * $containers = (new \Containers\Info($id, $this->pdo))->listContainers();
 *
 * return $response->withStatus(200)->withJson($containers);
 * } else {
 * return $response->withStatus(400)->withJson('{"error":"Missing required parameter ID"}');
 * }
 * });
 *
 * $app->patch('/api/docker/{id}/power', function (Request $request, Response $response, $args) {
 *
 * });
 *
 * $app->delete('/api/docker/{id}/delete', function (Request $request, Response $response, $args) {
 *
 * });*/

/**
 * ---------------------------------------------------------------------------------------------------------------------
 */

/**
 * ---------------------------------------------------------------------------------------------------------------------
 * UNAUTHENTICATED ACTIONS
 * ---------------------------------------------------------------------------------------------------------------------
 */

/**
 * -----------------------------------------------------------------------
 * USER SECTION
 * -----------------------------------------------------------------------
 */

$app->post('/api/user/create', function (Request $request, Response $response) {
    $body = $request->getParsedBody();

    if (isset($body) && !empty($body)) {
        $result = (new Register((array)$body, $this->pdo))->getFormData();
        if (is_array($result)) {
            return $response->withStatus(201)->withJson($result);
        } else {
            return $response->withStatus(400)->withJson(array(
                'status' => 'error',
                'message' => $result,
                'timestamp' => time()
            ));
        }
    } else {
        return $response->withStatus(400)->withJson(array(
            'status' => 'error',
            'message' => 'missing_body',
            'timestamp' => time()
        ));
    }
});

$app->get('/api/user/activation/email={email}&token={token}', function (Request $request, Response $response, $args) {
    $email = $args['email'];
    $token = $args['token'];

    $result = (new Users\usersActivationModel($this->pdo, $email, $token))->activateAccount();

    if ($result == "ok") {
        return $response->withStatus(200)->withJson(
            array(
                'status' => 'success',
                'message' => "account_activated",
                'timestamp' => time()
            ));
    } elseif ($result == "already_enabled") {
        return $response->withStatus(200)->withJson(
            array(
                'status' => 'error',
                'message' => "account_already_enabled",
                'timestamp' => time()
            ));
    } else {
        return $response->withStatus(400)->withJson(
            array(
                'status' => 'error',
                'message' => "bad_request",
                'timestamp' => time()
            ));
    }
});

$app->post('/api/user/login', function (Request $request, Response $response) {
    $body = $request->getParsedBody();

    if (isset($body) && !empty($body)) {
        $result = (new Login((array)$body, $this->pdo))->getFormData();
        if (is_array($result) && in_array('success', $result)) {
            return $response->withStatus(200)->withJson($result);
        } else {
            return $response->withStatus(400)->withJson($result);
        }
    } else {
        return $response->withStatus(400)->withJson(array(
            'status' => 'error',
            'message' => 'missing_body',
            'timestamp' => time()
        ));
    }
});

/**
 * ---------------------------------------------------------------------------------------------------------------------
 */

try {
    $app->run();
} catch (Throwable $e) {
    echo "Cannot run the app! " . $e->getMessage();
}