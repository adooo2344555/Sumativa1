<?php

namespace App\Http\Controllers;

use App\Models\Imagen;
use App\Models\Producto;
use Illuminate\Http\Request;

class ProductoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //hola
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
            // Validación de los datos
            $request->validate([
                "nombre" => "required|string",
                "descripcion" => "required|string",
                "precio" => "required|numeric",
                "material" => "required|string",
                "categoria.id" => "required|integer|exists:categorias,id"
            ]);
    
            // Obtención de datos correctamente
            $productoRequest = $request->all();
    
            // Verificar si ya existe un producto con esos datos (excluyendo el actual)
            $record = Producto::where("nombre", $productoRequest["nombre"])
                ->where("descripcion", $productoRequest["descripcion"])
                ->where("id", "!=", $producto->id)
                ->where("categoria_id", $productoRequest["categoria"]["id"])
                ->first();
    
            if ($record) {
                return response()->json(["message" => "Ya existe un producto con estos datos"], 409);
            }
    
            // Actualización del producto
            $producto->update([
                "nombre" => $productoRequest["nombre"],
                "descripcion" => $productoRequest["descripcion"],
                "precio" => $productoRequest["precio"],
                "material" => $productoRequest["material"],
                "categoria_id" => $productoRequest["categoria"]["id"]
            ]);
    
            // Manejo de imágenes (si las hay)
            if ($request->hasFile('imagenes')) {
                // Eliminar imágenes antiguas
                foreach ($producto->imagenes as $image) {
                    $imagePath = public_path('images/products/') . $image->nombre;
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $image->delete();
                }
    
                // Guardar nuevas imágenes
                foreach ($request->file('imagenes') as $img) {
                    $imageName = time() . '_' . $img->getClientOriginalName();
                    $img->move(public_path('images/products/'), $imageName);
                    Imagen::create([
                        'nombre' => $imageName,
                        'producto_id' => $producto->id
                    ]);
                }
            }
    
            return response()->json([
                "producto" => $this->show($producto->id),
                "message" => "Producto actualizado con éxito...!"
            ], 200);
    
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
             }else{
                return response()->json(["message" => "Ocurrió un error al eliminar el producto"],409);
            }
         }catch(\Exception $e) {
            return response()->json(['error'=>$e->getMessage()],500);
         }
    }
}
