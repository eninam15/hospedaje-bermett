<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category'); // 3 estrellas, 4 estrellas
            $table->text('address');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('city');
            $table->time('check_in_time')->default('14:00:00');
            $table->time('check_out_time')->default('12:00:00');
            $table->string('manager_name')->nullable();
            $table->text('description')->nullable();
            $table->text('qr_payment_info')->nullable(); // Info del QR de pago
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('branches');
    }
};