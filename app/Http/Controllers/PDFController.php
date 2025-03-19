<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\PDF as PDF;
use Carbon\Carbon;

class PDFController extends Controller
{
    public function getReservaciones(Request $request){
        // obteniendo los parametros del $request
        $fecha1 = $request->fechaInicio ?? now()->startOfMonth()->toDateString();
        $fecha2 = $request->fechaFinal ?? now()->endOfMonth()->toDateString();
        $estado = $request->estado;

        // generamos la consulta nativa para obtener los datos 
        $reservaciones = DB::select("
        SELECT r.id, r.fecha, r.estado,
        u.name as cliente,
        d.cantidad, 
        p.nombre as producto_nombre, p.descripcion as producto_descripcion, p.material, p.precio, 
        c.nombre as categoria,
        (d.cantidad * p.precio) as subtotal
        FROM reservaciones r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN detalle_reservaciones d ON d.reservacion_id = r.id
        INNER JOIN productos p ON d.producto_id = p.id
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE DATE(r.fecha) BETWEEN ? AND ?
        AND TRIM(r.estado) = ?
        ORDER BY r.id DESC
        ", [$fecha1, $fecha2, $estado]);

        // transformando los resultados en una estructura manejable
        $data = collect($reservaciones)->groupBy('id')->map(function ($reservacion){
            $detalle = $reservacion->map(function($item){
                return [
                    'nombre' => $item->producto_nombre,
                    'descripcion' => $item->producto_descripcion,
                    'material' => $item->material,
                    'categoria' => $item->categoria,
                    'cantidad' => $item->cantidad,
                    'precio' => $item->precio,  
                    'subtotal' => $item->subtotal,
                ];
            });
            // tomamos el primer registro ya que los datos se repiten en cada detalle
            $first = $reservacion->first();

            return [
                'id' => $first->id,
                'fecha' => $first->fecha,
                'cliente' => $first->cliente,
                'estado' => $first->estado,
                'total' => $detalle->sum('subtotal'),
                'detalle' => $detalle
            ];
        });

        // generar el PDF con los datos obtenidos
        $pdf = PDF::loadView('reportes.reservacionesPDF', compact('data', 'fecha1', 'fecha2', 'estado'));
        return $pdf->stream('reporte_reservaciones.pdf');
    }
}
