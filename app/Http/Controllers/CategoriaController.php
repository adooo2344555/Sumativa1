<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;

class CategoriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            return response()->json(Categoria::all());
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
            //primero verificamos que el nombre marca no existe
            $existeCategoria = Categoria::where("nombre",$request->nombre)->first();
            if($existeCategoria){
                return response()->json(["message"=>"Esta categoria ya esta registrada en la base de datos"],409);
            }else{
                $categoria = Categoria::create($request->all());
            return response()->json(["categoria"=>$categoria,"message"=>"categoria registrada con exito...!"],201);
            }
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try{
            return response()->json(Categoria::FindOrfail($id));
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Categoria $categoria)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,$id)
    {
        try{
            $existeCategoria = Categoria::where("nombre",$request->nombre)->first();
            if($existeCategoria && $existeCategoria->id != $id){
                return response()->json(
                    ["message"=>"Ya existe otro registro con esta categoria...!"],409);
            }else{
                $categoria = Categoria::findOrfail($id);
                $categoria->nombre = $request->nombre;
                if($categoria->update()>0)
                    return response()->json(["categoria"=>$categoria,"message"=>"Categoria actualizada..!"],202);
                else
                    return response()->json(["categoria"=>$categoria,"message"=>"Ocurrio un error al actualizar el registro..!"],500);
            }
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try{
            $categoria = Categoria::FindOrfail($id);
            if($categoria->delete()> 0){
                return response()->json(
                    ["message"=>"Categoria eliminada...!"],200);
            }else{
                return response()->json(["message"=>"Imposible eliminar el registro..!"],500);
            }
        }catch(\Exception $e){
            return response()->json(['error'=>$e->getMessage()],500);
        }
    }
}
