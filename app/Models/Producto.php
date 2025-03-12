<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre','descripcion','precio','material','categoria_id'];

    //relaciones pertenece "a" o la inversa de la relacion se pone public  function "marca" singular

    public function categoria(){
        return $this->belongsTo(Categoria::class);
    }
    //relacion de uno a muchos con imagenes se pone public  function "imagenes" plural
    public function imagenes(){
        return $this->hasMany(Imagen::class);
    }
    //relacion de uno a muchos con detalle_reservaciones
    public function DetalleReservaciones(){
        return $this->hasMany(DetalleReservacion::class);
    }
}
