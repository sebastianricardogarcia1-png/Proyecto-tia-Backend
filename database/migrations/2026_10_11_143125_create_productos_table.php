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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->enum('publico', ['mujer', 'hombre'])->default('mujer');
            $table->string('categoria');
            $table->text('descripcion')->nullable();
            $table->boolean('es_nuevo')->default(false);
            $table->boolean('es_destacado')->default(false);
            $table->boolean('activo')->default(true);
            $table->string('referencia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};
