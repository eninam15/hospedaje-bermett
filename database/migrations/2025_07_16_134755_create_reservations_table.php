<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->string('reservation_code')->unique(); // RES + 6 dígitos
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('total_nights');
            $table->integer('adults_count')->default(1);
            $table->integer('children_count')->default(0);
            $table->boolean('needs_parking')->default(false);
            $table->decimal('parking_fee', 8, 2)->nullable();
            $table->decimal('room_total', 10, 2);
            $table->decimal('services_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', [
                'pending_payment', 
                'payment_submitted', 
                'confirmed', 
                'checked_in', 
                'completed', 
                'cancelled'
            ])->default('pending_payment');
            $table->text('special_requests')->nullable();
            $table->enum('payment_method', ['qr', 'cash'])->default('qr');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
};