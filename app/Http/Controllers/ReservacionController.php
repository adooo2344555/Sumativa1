<?php

namespace App\Http\Controllers;

use App\Models\DetalleReservacion;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Models\Reservacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Validator;

class ReservacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            return response()->json(Reservacion::with('user', 'detalleReservaciones.producto')->get());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validando datos del $request
            $validatedData = $request->validate([
                'fecha' => 'required|date',
                'estado' => 'required|string',
                'user.id' => 'required|exists:users,id',
                'detalleReservaciones' => 'required|array|min:1',
                'detalleReservaciones.*.cantidad' => 'required|numeric|min:1',
                'detalleReservaciones.*.subtotal' => 'required|numeric',
                'detalleReservaciones.*.producto.id' => 'required|exists:productos,id',
            ]);

            // Extraer user_id del objeto user
            $userId = $validatedData['user']['id'];

            // Iniciamos la transacción
            DB::beginTransaction();

            // Guardando el registro de reservación (tabla reservacion)
            $reservacion = Reservacion::create([
                'fecha' => $validatedData['fecha'],
                'estado' => $validatedData['estado'],
                'user_id' => $userId
            ]);

            // Crear el detalle de la reservación
            $detalleData = collect($validatedData['detalleReservaciones'])->map(function ($det) use ($reservacion) {
                return [
                    'cantidad' => $det['cantidad'],
                    'subtotal' => $det['subtotal'],
                    'reservacion_id' => $reservacion->id,
                    'producto_id' => $det['producto']['id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();
            DetalleReservacion::insert($detalleData);

            // Confirmamos la transacción
            DB::commit();
            return response()->json([
                "reservacion" => $reservacion->load('detalleReservaciones'),
                "message" => "Su reservacion ha sido registrada correctamente"
            ], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            return response()->json(Reservacion::with('user', 'detalleReservaciones.producto')->FindOrfail($id));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservacion $reservacion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $reservacion = Reservacion::find($id);
            if (!$reservacion) {
                return response()->json([
                    'status' => 'Not found',
                    'message' => 'Orden no encontrada'
                ], 404);
            }
            //definimos estados de la orden
            $estados = ['C' => 'Confirmada', 'P' => 'Pendiente', 'Ca' => 'Cancelada'];
            //verificamos el estado de envio
            if (!array_key_exists($request->estado, $estados)) {
                return response()->json([
                    'status' => 'Bad Request',
                    'message' => 'estado invalido'
                ], 400);
            }
            //Obtenemos la fecha del servidor
            $fechActual = Carbon::now()->toDateString(); //formato yyyy-mm-dd
            if ($request->estado === 'D') {
                $reservacion->fecha_despacho = $fechActual;
            }
            //asignamos el nuevo estado de la orden
            $reservacion->estado = $request->estado;
            $reservacion->save();
            return response()->json([
                'status' => 'Accepted',
                //'message' => 'El estado de la reservacion No ' .$reservacion->correlativo. 'ha sido cambiado a :' .$estados[$reservacion->estado] 
            ], 202);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            // Buscar la reservación por ID
            $reservacion = Reservacion::find($id);

            // Verificar si la reservación existe
            if (!$reservacion) {
                return response()->json([
                    'status' => 'Not found',
                    'message' => 'Reservación no encontrada'
                ], 404);
            }

            // Eliminar los detalles de la reservación
            $reservacion->detalleReservaciones()->delete();

            // Eliminar la reservación
            $reservacion->delete();

            return response()->json([
                'status' => 'Deleted',
                'message' => 'Reservación eliminada correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
