<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Mesa;
use App\Models\Estado_Mesa;

use App\Models\Usuario;

use \Firebase\JWT\JWT;

class MesaController
{

    public function getOne(Request $request, Response $response, $args)
    {
        $rta = Mesa::find($args['id']);
        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function getAll(Request $request, Response $response, $args)
    {
        $rta = Mesa::get();
        $response->getBody()->write(json_encode($rta));
        return $response;
    }
    //cambiar estados mesa (mozo || socio)
    public function UpdateMesa(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
            $mesa = Mesa::find($args['id']);
            if ($mesa->idEstado < 3 && ($tipo == 'socio' || $tipo == 'mozo')) {
                $mesa->idEstado++;
                $mesa->save();
                $rta = "El estado actual de la mesa es ".Estado_Mesa::find($mesa->idEstado)->estadoMesa;
            } else if ($mesa->idEstado === 3 && $tipo === 'socio') {
                $mesa->idEstado++;
                $mesa->save();
                $rta = "Se cerró la mesa con éxito.";
            } else {
                $rta = "Solo los socios pueden cerrar la mesa";
                $response->withStatus(401);

            }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }
    //crear o borrar mesa (solo socios)
    public function addOne(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        if ($tipo == 'socio') {
            $mesa = new Mesa;
            $mesa->save();
            $rta = "Se creó la mesa con éxito";
        } else {
            $rta = "Solo los socios pueden borrar mesas...";
            $response->withStatus(401);
        }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    public function deleteOne(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        if ($tipo == 'socio') {
            $mesa = Mesa::find($args['id']);

            $rta = $mesa->delete();
        } else {
            $rta = "Solo los socios pueden borrar mesas...";
            $response->withStatus(401);
        }
        $response->getBody()->write(json_encode($rta));
        return $response;
    }
}
