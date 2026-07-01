<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('peserta_ujian', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kelas');
            $table->string('no_peserta', 7)->unique();
            $table->enum('status', ['Activated', 'Deactivated'])->default('Activated');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('peserta_ujian');
    }
};
