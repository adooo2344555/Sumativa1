<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});
 */
//Rutaz raiz para mostrar catalalogos de productos

Route::get('/', function(){
    return Inertia::render('Catalogo');
})->name('catalogo');


Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

//rutas para acceder alas vistas
Route::get('/reservaciones', function(){
    return Inertia::render('catalogos/Reservaciones');
})->middleware(['auth', 'verified'])->name('reservaciones'); 

//rutas para acceder ala de productos
Route::get('/productos', function(){
    return Inertia::render('catalogos/Productos');
})->middleware(['auth', 'verified'])->name('productos');

//rutas para acceder ala de productos
Route::get('/categorias', function(){
    return Inertia::render('catalogos/Categorias');
})->middleware(['auth', 'verified'])->name('categorias');

//rutas para acceder ala de productos
Route::get('/reservaciones', function(){
    return Inertia::render('reservaciones/Reservaciones');
})->middleware(['auth', 'verified'])->name('reservaciones');

require __DIR__.'/auth.php';
