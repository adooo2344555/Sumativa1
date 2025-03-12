<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imagen extends Model
{
    use HasFactory;
    protected $table = "imagenes";

    protected $fillable = ['nombre','producto_id'];

    public function producto(){
        return $this->belongsTo(Producto::class);
    }
}
