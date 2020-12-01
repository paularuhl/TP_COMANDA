<?php

namespace App\Middlewares;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use \Firebase\JWT\JWT;
use \Controllers\UsuarioController;
use Throwable;

class AuthMiddleware
{
    /**
     * Example middleware invokable class
     *
     * @param  ServerRequest  $request PSR-7 request
     * @param  RequestHandler $handler PSR-15 request handler
     *
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler): Response
    {

        $token = getallheaders()['token'] ?? '';
        $decoded = null;
        try {
            $decoded = JWT::decode($token, "comanda", array('HS256'));
        } catch (Throwable $th) {
            $msj = $th->getMessage();
        }

        if ($decoded == null) {
            $response = new Response();
            $response->getBody()->write("Prohibido pasar");

            return $response->withStatus(403);
        } else {
            
            $req = $request->getParsedBody();
            $req['token'] = $decoded;
            $request = $request->withParsedBody($req);
            $response = $handler->handle($request);
            $existingContent = (string) $response->getBody();
            $resp = new Response();
            $resp->getBody()->write($existingContent);
            return $resp;
        }
    }
}
