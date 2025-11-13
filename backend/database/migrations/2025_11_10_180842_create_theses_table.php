<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('theses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('file_path');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('theses');
    }
};
