<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
     public $timestamps = true;
     protected $primaryKey = 'idPedido';
     protected $casts = ['idPedido' => 'string'];

}