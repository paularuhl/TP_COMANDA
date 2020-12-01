<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Config\Database;
use Slim\Routing\RouteCollectorProxy;
use App\Controllers\UsuarioController;
use App\Controllers\PedidoController;
use App\Controllers\LoginController;

use App\Middlewares\JsonMiddleware;
use App\Middlewares\AuthMiddleware;

require __DIR__ . '/../vendor/autoload.php'; // se encarga de incluir todas las dependencias


$app = AppFactory::create();
$app->setBasePath("/public_html/public");
new Database;

$app->group('/users', function (RouteCollectorProxy $group) {

    //get all, get one
    $group->get('', UsuarioController::class . ":getAll");
    $group->get('/{id}', UsuarioController::class . ":getOne");

    //crear
    $group->post('', UsuarioController::class . ":addOne");

    //put but let it try
    $group->post('/{id}', UsuarioController::class . ":cambiarArea");

    //TODO: cambiar a "update" y switchear entre suspendido y activo. 
    $group->post('/suspender/{id}', UsuarioController::class . ":suspender");

    //delete
    $group->delete('/borrar/{id}', UsuarioController::class . ":borrarEmpleado");


})->add(new JsonMiddleware);

$app->group('/login', function (RouteCollectorProxy $group) {

    $group->post('', LoginController::class . ":login");

})->add(new JsonMiddleware);

$app->group('/clientes', function (RouteCollectorProxy $group) {

    $group->get('', PedidoController::class . ":verTiempoRestante");

})->add(new JsonMiddleware);


$app->group('/pedidos', function (RouteCollectorProxy $group) {

    $group->get('/{id}', PedidoController::class . ":getOne")->add(new AuthMiddleware);

    $group->get('', PedidoController::class . ":getAll")->add(new AuthMiddleware);

    $group->post('', PedidoController::class . ":addOne")->add(new AuthMiddleware);

    $group->delete('', PedidoController::class . ":deleteOne")->add(new AuthMiddleware);

    $group->post('/prepararPendiente', PedidoController::class . ":preparacion")->add(new AuthMiddleware);
     
    $group->post('/pedidoListo', PedidoController::class . ":servirPedido")->add(new AuthMiddleware);


})->add(new JsonMiddleware);

$app->group('/mesas', function (RouteCollectorProxy $group) {

    $group->get('/{id}', MesaController::class . ":getOne")->add(new AuthMiddleware);

    $group->get('', MesaController::class . ":getAll")->add(new AuthMiddleware);

    $group->post('', MesaController::class . ":addOne")->add(new AuthMiddleware);

    $group->delete('', MesaController::class . ":deleteOne")->add(new AuthMiddleware);


})->add(new JsonMiddleware);


$app->run();
