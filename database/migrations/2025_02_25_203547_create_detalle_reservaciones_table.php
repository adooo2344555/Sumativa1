<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('detalle_reservaciones', function (Blueprint $table) {
            $table->id();
            $table->decimal('cantidad',8,2);
            $table->decimal('subtotal',10,2);
            $table->unsignedBigInteger('reservacion_id');
            $table->foreign('reservacion_id')->references('id')->on('reservaciones');
            $table->unsignedBigInteger('producto_id');
            $table->foreign('producto_id')->references('id')->on('productos');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detalle_reservaciones');
    }
};
