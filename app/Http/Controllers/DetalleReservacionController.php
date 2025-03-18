<?php

namespace App\Http\Controllers;

use App\Models\DetalleReservacion;
use Illuminate\Http\Request;

class DetalleReservacionController extends Controller
{
    public function index()
    {
        return DetalleReservacion::all();
    }

    public function show($id)
    {
        return DetalleReservacion::where('reservacion_id', $id)->get();
    }
}