<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->enum('document_type', ['ci', 'passport', 'other'])->default('ci');
            $table->string('document_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->string('profile_photo')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'document_type', 'document_number', 
                'birth_date', 'address', 'profile_photo', 'is_active'
            ]);
        });
    }
};