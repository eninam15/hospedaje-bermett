<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->string('room_number');
            $table->integer('floor');
            $table->decimal('price_per_night', 10, 2); // Precio en Bs
            $table->enum('status', ['available', 'occupied', 'maintenance', 'cleaning'])
                  ->default('available');
            $table->text('description')->nullable();
            $table->json('photos')->nullable(); // Array de URLs
            $table->json('amenities')->nullable(); // Comodidades específicas
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['branch_id', 'room_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('rooms');
    }
};