<?php

namespace App\Controllers;

use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use \Firebase\JWT\JWT;

use App\Models\Usuario;

class LoginController
{
    public function login(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();

        $id = isset($body['email']) ? 'email' : null;

        if ($id != null) {
            $user = Usuario::where($id, '=', strtolower($body[$id]))->where('password', '=', $body['password'])->get()->first();
        }
        if (!$user) {
            $response = new Response();
            $response->getBody()->write("Usuario inexistente");

            return $response->withStatus(401);
        } else {
            $encodeOk = false;
            $payload = array();

            $payload = array(
                "email" => $user->email,
                "tipo" => $user->tipo
            );
            $encodeOk = JWT::encode($payload, "comanda");
            $response = new Response();
            $response->getBody()->write(json_encode($encodeOk));

            return $response->withStatus(200);
        }
    }
}
