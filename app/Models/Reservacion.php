<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservacion extends Model
{
    use HasFactory;

    protected $table = "reservaciones";

    protected $fillable = ['fecha','estado','user_id'];

    //relacion con el modelo User
    public function user(){
        return $this->belongsTo(User::class);
    }
    //relacion con el modelo DetalleOrden
    public function detalleReservaciones(){
        return $this->hasMany(DetalleReservacion::class);
    }
}
