<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;


class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            return response()->json(Producto::with('categoria','imagenes')->get());
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
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
        try{
            /*hacemos una consulta para verificar que no exista un producto con el 
            mismo nombre
            */
            //decodificamos el request
            $productoRequest = json_decode($request->input("producto"),true);
               
            $record = Producto::where("nombre", $productoRequest["nombre"] ?? null)
            ->where("descripcion", $productoRequest["descripcion"] ?? null)
            ->whereHas('categoria', function($query) use ($productoRequest) {
                if (isset($productoRequest["categoria"]["nombre"])) {
                    $query->where("nombre", $productoRequest["categoria"]["nombre"]);
                }
            })
            ->first();
            if($record){
                return response()->json(
                    ["message"=>"Ya existe un registro de producto con estos datos"],409);
            } 
            //creamos una instancia de producto
            $producto = new Producto();
            $producto->nombre = $productoRequest["nombre"];
            $producto->descripcion = $productoRequest["descripcion"];
            $producto->precio = $productoRequest["precio"];
            $producto->material = $productoRequest["material"];
            $producto->categoria_id = $productoRequest["categoria"]["id"];
            $producto->save(); //guardamos en la tabla de productos
            //verificamos si la peticion trae imagenes
            if($request->hasFile('imagenes')){
                //recorremos la coleccion de imagenes
                foreach($request->file('imagenes') as $img){
                    //generamos un nombre único de la imagen a partir del original
                    $imageName = time() . '_' . $img->getClientOriginalName();
                    //subimos la imagen a la carpeta publica del servidor
                    $img->move(public_path('images/products/'),$imageName);
                    //creamos la instancia de Imagen para guardar los registros
                    $image = new Imagen();
                    $image->nombre = $imageName;
                    $image->producto_id = $producto->id;
                    $image->save(); 
                }
            }
            $prodPersisted = $this->show($producto->id);
            return response()->json(["producto"=>$prodPersisted,
                "message"=>"Producto registrado con éxito...!"],201);
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //Si lo busca por id en pos le va aparecer el producto con ese id e igual marca,categoria etc
        try{
            return response()->json(Producto::with('categoria','imagenes')->findOrFail($id));
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Producto $producto)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Producto $producto)
    {
        try {
            // Verificar si ya existe un producto con el mismo nombre y diferente ID
            $existeProducto = Producto::where("nombre", $request->nombre)->where("id", "!=", $producto->id)->first();
            if ($existeProducto) {
                return response()->json(["message" => "Ya existe un producto con este nombre"], 409);
            }
        
            // Actualizar el producto con los nuevos datos
            $producto->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'precio' => $request->precio,
                'material' => $request->material,
                'categoria_id' => $request->categoria, 
            ]);
        
            // Eliminar imágenes existentes
            if ($producto->imagenes) {
                foreach ($producto->imagenes as $image) {
                    $imagePath = public_path('images/products/' . $image->nombre);
                    if (file_exists($imagePath)) {
                        unlink($imagePath); // Eliminar archivo físico
                    }
                    $image->delete(); // Eliminar registro en la base de datos
                }
            }
        
            // Agregar nuevas imágenes si están presentes en la solicitud
            if($request->hasFile('imagenes')){
                //recorremos la colección de imagenes para guardarlas en "imagenes"
                foreach($request->file('imagenes') as $img){
                    //creamos un nombre único de la imagen
                    $imageName = time() . '_' . $img->getClientOriginalName();
                    //subimos el archivo de imagen a una carpeta publica del servidor
                    $img->move(public_path('images/products/'),$imageName);
                    //creamos la instancia de Imagen para luego guardar cada registro en 
                    //la tabla de imagenes
                    $image = new Imagen();
                    $image->nombre = $imageName;
                    $image->producto_id = $producto->id;
                    $image->save();
                }
            }
            // Obtener el producto actualizado con sus relaciones
            $prodPersisted = Producto::with([ 'categoria', 'imagenes'])->findOrFail($producto->id);
        
            return response()->json(['producto' => $prodPersisted, 'message' => 'Producto actualizado correctamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try{
            $producto = Producto::findOrFail($id);
            /* Eliminamos las imágenes del servidor y en las carpetas 
            de nuestra lap ala hora de eliminar el registro */
            foreach($producto->imagenes as $image){
                $imagePath = public_path() . '/images/products/' . $image->nombre;
                unlink( $imagePath);
            }
            // Eliminamos los registros de la tabla de imágenes
             $producto->imagenes()->delete();
            if($producto->delete() > 0){
                return response()->json(["message" => "Producto eliminado"],205);
            }
        } catch(QueryException $e) {
            if($e->getCode() == "23000") {
                return response()->json([
                    'error' => 'No se puede eliminar este producto, porque tiene registros relacionados'], 409);
            }
            return response()->json(['error' => 'Error en la base de datos: ' . $e->getMessage()], 500);
        }
         catch(\Exception $e) {
            return response()->json(['error' => 'Error inesperado: ' . $e->getMessage()], 500);
        }
    }
}
