<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Usuario;

use App\Models\Pedido;
use App\Models\Estado_Pedido;
use App\Models\Pedido_Comida;
use App\Models\Mesa;
use App\Models\Mesa_Pedido;
use App\Models\Estado_Mesa;

use App\Models\Comida;
use Exception;
use \Firebase\JWT\JWT;
use Throwable;

class PedidoController
{

    public function getOne(Request $request, Response $response, $args)
    {
        $rta = Pedido::find($args['id']);
        $response->getBody()->write(json_encode($rta));
        return $response;
    }


    public function getAll(Request $request, Response $response, $args)
    {
        $rta = Pedido::get();
        $response->getBody()->write(json_encode($rta));
        return $response;
    }


    public function addOne(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;

        if ($tipo == 'mozo') {


            $nuevoId = "A";

            $pedidos = Pedido::get();
            $cantidadPedidos = count($pedidos);
            if ($cantidadPedidos > 0) {
                $nuevoId = "A" . ((int) substr(($pedidos[$cantidadPedidos - 1])->idPedido, 1) + 1);
            } else {
                $nuevoId = $nuevoId . "1000";
            }
            $pedido = new Pedido;

            $mesa = Mesa::find($body['mesa']);

            if ($mesa->idEstado === Estado_Mesa::select("*")->where("id", "=", 4)->get()->first()->id) {
                $pedido->idPedido = $nuevoId;
                $pedido->nombre_cliente = $body['nombreCliente'];
                $pedido->idMesa = $body['mesa'];
                $pedido->idEstado = 1;
                $pedido->tiempoEstimadoPreparacion = rand(15, 45);
                $pedido->tiempoRestantePedido = $pedido->tiempoEstimadoPreparacion;
                $pedido->costoTotal = $this->asignarComidas($body, $nuevoId);
                $pedido->save();
                $mesaPedido = new Mesa_Pedido;
                $mesaPedido->idPedido = $nuevoId;
                $mesaPedido->idMesa = $pedido->idMesa;
                $mesa->idEstado = Estado_Mesa::select("*")->where("id", "=", 1)->get()->first()->id;
                $mesa->save();
                $mesaPedido->save();

                $rta = "Nro de Pedido: $nuevoId";
            } else {
                $rta = "Esa mesa está ocupada en este momento...";
            }
        } else {
            $rta = "El usuario $tipo no tiene acceso a agregar pedidos...";
            $response->withStatus(401);
        }

        $response->getBody()->write(json_encode($rta));
        return $response;
    }

    //El mozo asigna el pedido y retorna el precio total de la lista de productos
    private function asignarComidas($body, $nuevoId)
    {
        $costoTotal = 0;
        $comidas = json_decode($body['comidas']);
        foreach ($comidas as $value) {
            $pedidoComida = new Pedido_Comida;
            $pedidoComida->idPedido = $nuevoId;
            $pedidoComida->idEstado = 1;
            $pedidoComida->idComida = $value->idComida;
            $pedidoComida->idMozo = Usuario::select("idUsuario")->where("email", "=", $body['token']->email)->get()->first()->idUsuario;
            $comida = Comida::select("*")->where("id", "=", $value->idComida)->get()->first()->tipo;

            $costoTotal += $comida->precio;
            $pedidoComida->idEmpleado = null;
            // $pedidoComida->idEmpleado = Usuario::select("*")->where("tipo", "=", $sectores[$comida->tipo])->where("estado", "=", "activo")->get()->first()->idUsuario;
            $pedidoComida->save();
        }
        return $costoTotal;
    }

    public function preparacion(Request $request, Response $response, $args)
    {
        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        $sectores = ["socio", "bartender", "cervecero", "cocinero", "cocinero"];

        $pedidoComida = Pedido_Comida::select("*")->where("idEstado", "=", 1)->get()->first();
        $comida = Comida::select("*")->where("id", "=", $pedidoComida->idComida)->get()->first();

        if ($sectores[$comida->tipo] === $tipo || $tipo === $sectores[0]) {
            $pedidoComida->idEmpleado = Usuario::select("*")->where("tipo", "=", $sectores[$comida->tipo])->get()->first()->idUsuario;
            if ($pedidoComida->idEstado < 2) {
                $pedidoComida->idEstado++;
                $pedidoComida->save();
            }
            $rta = "Se actualizó el estado de " . $comida->nombre . " a " . Estado_Pedido::find($pedidoComida->idEstado)->estadoPedido;
        } else {
            $rta = "No hay pedidos pendientes en tu sector!";
            $response->withStatus(404);
        }
        return $this->modificarItems($pedidoComida, $rta, $response);
    }

    public function servirPedido(Request $request, Response $response, $args)
    {

        $body = $request->getParsedBody();
        $tipo = $body['token']->tipo;
        $empleado = $body['token']->email;
        $sectores = ["socio", "bartender", "cervecero", "cocinero", "cocinero"];

        $pedidoComida = Pedido_Comida::select("*")->where("idEstado", "=", 1)->get()->first();
        $comida = Comida::select("*")->where("id", "=", $pedidoComida->idComida)->get()->first();
        $idEmpleado = Usuario::select("*")->where("email", "=", $empleado)->get()->first()->idUsuario;

        if ($idEmpleado === $pedidoComida->idEmpleado || $tipo === $sectores[0]) {
            if ($pedidoComida->idEstado < 2) {
                $pedidoComida->idEstado++;
                $pedidoComida->save();
            }
            $rta = "Se actualizó el estado de " . $comida->nombre . " a " . Estado_Pedido::find($pedidoComida->idEstado)->estadoPedido;
        } else {
            $rta = "No hay pedidos en preparación en tu sector!";
            $response->withStatus(404);
        }
        // $pedidosMismaComanda = Pedido_Comida::select("*")->where("idPedido", "=", $pedidoComida->idPedido)->get();

        // $estado = 1;
        // $aux = true;
        // for ($i = 0; $i < count($pedidosMismaComanda) - 1; $i++) {
        //     if ($i === 0) {
        //         $estado = $pedidosMismaComanda[$i]->idEstado;
        //     } else if ($estado !== $pedidosMismaComanda[$i]->idEstado) {
        //         $aux = false;
        //         break;
        //     }
        // }
        // if ($aux) {
        //     $pedido = Pedido::find($pedidoComida->idPedido);
        //     $pedido->tiempoEstimadoPreparacion = rand(20, 45);
        //     $pedido->idEstado = $pedidoComida->idEstado;
        //     $pedido->save();
        //     $rta = $rta . " El pedido " . $pedido->idPedido . " se encuentra en su totalidad en " . Estado_Pedido::find($pedido->idEstado)->estadoPedido;
        // }

        // $response->getBody()->write(json_encode($rta));
        $response = $this->modificarItems($pedidoComida, $rta, $response);
        return $this->modificarItems($pedidoComida, $rta, $response);
    }

    private function modificarItems($pedidoComida, $rta, $response)
    {
        $pedidosMismaComanda = Pedido_Comida::select("*")->where("idPedido", "=", $pedidoComida->idPedido)->get();

        $estado = 1;
        $aux = true;
        for ($i = 0; $i < count($pedidosMismaComanda) - 1; $i++) {
            if ($i === 0) {
                $estado = $pedidosMismaComanda[$i]->idEstado;
            } else if ($estado !== $pedidosMismaComanda[$i]->idEstado) {
                $aux = false;
                break;
            }
        }
        if ($aux) {
            $pedido = Pedido::find($pedidoComida->idPedido);
            $pedido->idEstado = $pedidoComida->idEstado;
            if ($pedidoComida->idEstado === 2) {
                $pedido->tiempoEstimadoPreparacion = rand(20, 45);
            }
            $pedido->save();
            $rta = $rta . " El pedido " . $pedido->idPedido . " se encuentra en su totalidad en " . Estado_Pedido::find($pedido->idEstado)->estadoPedido;
        }

        $response->getBody()->write(json_encode($rta));
        return $response;
    }


    public function verTiempoRestante(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();

        $pedido = Pedido::find($params['idPedido']);
        $pedido->tiempoRestantePedido = rand(1, 20);
        $pedido->save();
        $rta = "El tiempo restante para recibir su pedido es de ".$pedido->tiempoRestantePedido." minutos.";
        $response->getBody()->write(json_encode($rta));
        return $response;

    }
}



// else if ($pedidoComida->idEstado === 3) {
//     $pedido->tiempoRestantePedido = rand(1, 20);
// }