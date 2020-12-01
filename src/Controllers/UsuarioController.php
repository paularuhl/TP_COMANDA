<?php

namespace App\Controllers;

use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Firebase\JWT\JWT;

use App\Models\Usuario;



class UsuarioController
{
    public const roles = ["admin", "socio", "mozo","bartender","cervecero","cocinero"];
    public const estado = ["suspendido", "activo"];


    public function getAll(Request $request, Response $response, $args)
    {
        $rta = Usuario::get();

        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function getOne(Request $request, Response $response, $args)
    {
        $rta = Usuario::find($args['id']);

        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function addOne(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $user = new Usuario;
        $rta = "";
        $users = Usuario::get();
        $unique = true;

        if (strlen($body["password"]) > 7) {
            if (count($users) > 0) {
                foreach ($users as $value) {
                    if ($value->email == $body['email']) {
                        $unique = false;
                        break;
                    }
                }
            }

            if ($unique) {
                $user->email = strtolower($body['email']);
                $user->password = $body['password'];
                $user->tipo = $body['tipo'];
                $user->estado = "activo";

                $rta = $user->save();
            } else {
                $rta = "Nombre o Email repetidos... no se puede registrar.";
            }
        } else {
            $rta = "La password tiene que tener al menos 8 caracteres. No se puede registrar.";
        }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function cambiarArea(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        if ($tipo == 'socio') {

            $user = Usuario::find($args['id']);
            $body = $request->getParsedBody();
            $user->tipo = $body['tipo'];

            $rta = $user->save();
        } else {
            $rta = "Solo los socios pueden modificar a los empleados...";
        }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function suspender(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        if ($tipo == 'socio') {
            $user = Usuario::find($args['id']);

            $user->estado = "suspendido";
            $rta = $user->save();
        } else {
            $rta = "Solo los socios pueden modificar a los empleados...";
        }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }


    //despedir??
    public function borrarEmpleado(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        if ($tipo == 'socio') {
            $user = Usuario::find($args['id']);

            $rta = $user->delete();
        } else {
            $response->withStatus(401);
            $rta = "Solo los socios pueden modificar a los empleados...";
        }
        
        $response->getBody()->write(json_encode($rta));
        return $response;
    }
}
