<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleReservacion extends Model
{
    use HasFactory;
    protected $table = "detalle_reservaciones";
    protected $fillable = ['cantidad','subtotal','reservacion_id','producto_id'];

    //relacion con el modelo producto
    public function producto(){
        return $this->belongsTo(Producto::class);
    }
    //relacion con orden
    public function reservacion(){
        return $this->belongsTo(Reservacion::class);
    }
}
